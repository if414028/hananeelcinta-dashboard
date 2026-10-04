<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Auth\Contracts\FirebaseTokenVerifier;
use App\Auth\VerifiedFirebaseToken;
use App\Exceptions\FirebaseAuthUnavailableException;
use App\Exceptions\FirebaseTokenException;
use App\Models\Congregation;
use App\Models\MobileAccount;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MobileRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_uses_firebase_identity_and_returns_profile_available_on_subsequent_login(): void
    {
        $this->verifyAs('new-uid', 'new@example.com');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer firebase-id-token',
            'X-App-Platform' => 'ios',
            'X-App-Version' => '3.0.0',
        ])->postJson('/api/v1/auth/register', array_merge($this->profile(), [
            'full_name' => 'Jemaat Baru',
            'gender' => 'female',
            'date_of_birth' => '1995-06-20',
            'address' => 'Jalan Gereja 1',
            'city' => 'Jakarta',
            'phone_number' => '+628123456789',
            // Untrusted identity and administrative fields must never be mass assigned.
            'uid' => 'victim-uid',
            'legacy_firebase_uid' => 'victim-uid',
            'email' => 'victim@example.com',
            'member_number' => 'FORGED',
            'membership_status' => 'member',
            'is_active' => false,
            'created_by' => 999,
            'notes' => 'Injected internal notes',
        ]));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account.uid', 'new-uid')
            ->assertJsonPath('data.account.email', 'new@example.com')
            ->assertJsonPath('data.account.email_verified', true)
            ->assertJsonPath('data.profile.full_name', 'Jemaat Baru')
            ->assertJsonPath('data.profile.membership_status', 'visitor')
            ->assertJsonPath('data.profile.baptism_status', 'unknown')
            ->assertJsonPath('data.profile.is_active', true)
            ->assertJsonPath('data.profile.address.street', 'Jalan Gereja 1')
            ->assertJsonPath('data.profile.profile_photo_url', null)
            ->assertJsonMissingPath('data.profile.notes')
            ->assertJsonMissingPath('data.profile.legacy_firebase_uid')
            ->assertHeader('Cache-Control', 'no-store, private');

        $id = $response->json('data.profile.id');
        $this->assertDatabaseHas('congregations', [
            'id' => $id, 'legacy_firebase_uid' => 'new-uid', 'email' => 'new@example.com',
            'created_by' => null, 'notes' => null,
        ]);
        $this->assertMatchesRegularExpression('/^HC-\d{4}-\d{5}$/', $response->json('data.profile.member_number'));
        $this->assertDatabaseHas('mobile_accounts', [
            'firebase_uid' => 'new-uid', 'congregation_id' => $id, 'last_platform' => 'ios', 'last_app_version' => '3.0.0',
        ]);

        // Reads must use the current CMS profile, rather than stale registration or Firebase claims.
        Congregation::findOrFail($id)->update(['full_name' => 'Nama diperbarui di CMS']);
        $this->withToken('firebase-id-token')->getJson('/api/v1/me')->assertOk()
            ->assertJsonPath('data.profile.full_name', 'Nama diperbarui di CMS');
        $this->withToken('firebase-id-token')->postJson('/api/v1/auth/session')->assertOk()
            ->assertJsonPath('data.profile.id', $id);
    }

    public function test_json_registration_persists_the_complete_profile_on_register_me_and_session(): void
    {
        $this->verifyAs();
        $payload = array_merge($this->profile(), [
            'nickname' => 'Jemaat', 'baptism_status' => 'baptized', 'baptism_date' => '2015-01-02',
            'baptism_church' => 'Gereja Baptis', 'holy_spirit_baptism' => false,
            'church_origin' => 'Gereja Asal', 'reason_to_move_church' => 'Pindah domisili',
            'family_status' => 'Single - Belum Menikah', 'wife_name' => 'Nama Istri',
            'husband_name' => 'Nama Suami', 'children_names' => ['Anak Satu', 'Anak Dua'],
            'siblings_names' => ['Saudara Satu', 'Saudara Dua'],
        ]);
        $response = $this->withToken('token')->postJson('/api/v1/auth/register', $payload)->assertCreated();
        $expected = array_merge($payload, ['phone_number' => '+628123456789']);
        unset($expected['address']);

        foreach ([$response, $this->withToken('token')->getJson('/api/v1/me')->assertOk(), $this->withToken('token')->postJson('/api/v1/auth/session')->assertOk()] as $profileResponse) {
            foreach ($expected as $field => $value) {
                $profileResponse->assertJsonPath('data.profile.'.$field, $value);
            }
            $profileResponse->assertJsonPath('data.profile.address.street', $payload['address']);
        }

        $stored = Congregation::findOrFail($response->json('data.profile.id'));
        $this->assertSame(false, $stored->holy_spirit_baptism);
        $this->assertSame($payload['children_names'], $stored->children_names);
        $this->assertSame($payload['siblings_names'], $stored->siblings_names);
        $this->assertSame('D1, D2, D3', $stored->last_education);
    }

    #[DataProvider('multipartBooleans')]
    public function test_multipart_registration_stores_jpeg_and_returns_typed_profile_after_login(string $boolean, bool $expected): void
    {
        Storage::fake('public');
        $this->verifyAs();
        $payload = array_merge($this->profile(), [
            'baptism_status' => 'not_baptized', 'baptism_church' => 'Gereja tetap disimpan',
            'holy_spirit_baptism' => $boolean, 'children_names' => ['Anak Satu', 'Anak Dua'],
            'siblings_names' => ['Saudara Satu', 'Saudara Dua'],
            'profile_photo' => UploadedFile::fake()->image('selfie.jpeg')->size(5120),
        ]);
        $response = $this->withToken('token')->withHeaders(['Accept' => 'application/json', 'X-App-Platform' => 'android'])
            ->post('/api/v1/auth/register', $payload)->assertCreated();
        $stored = Congregation::findOrFail($response->json('data.profile.id'));
        Storage::disk('public')->assertExists($stored->profile_photo);
        $url = Storage::disk('public')->url($stored->profile_photo);

        foreach ([$response, $this->withToken('token')->getJson('/api/v1/me')->assertOk(), $this->withToken('token')->postJson('/api/v1/auth/session')->assertOk()] as $profileResponse) {
            $profileResponse->assertJsonPath('data.profile.holy_spirit_baptism', $expected)
                ->assertJsonPath('data.profile.profile_photo_url', $url)
                ->assertJsonPath('data.profile.children_names', $payload['children_names'])
                ->assertJsonPath('data.profile.siblings_names', $payload['siblings_names'])
                ->assertJsonPath('data.profile.baptism_church', $payload['baptism_church'])
                ->assertJsonPath('data.profile.baptism_date', null);
        }

        // A retry after a lost response must leave the original profile and photo intact.
        $payload['profile_photo'] = UploadedFile::fake()->image('retry.jpg');
        $this->post('/api/v1/auth/register', $payload)->assertConflict();
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('congregations', 1);
        Storage::disk('public')->assertExists($stored->profile_photo);
    }

    public static function multipartBooleans(): array
    {
        return ['selected' => ['1', true], 'unselected' => ['0', false]];
    }

    public function test_empty_optional_text_and_array_entries_are_ignored(): void
    {
        $this->verifyAs();
        $this->withToken('token')->postJson('/api/v1/auth/register', array_merge($this->profile(), [
            'nickname' => '  ', 'baptism_status' => '', 'baptism_church' => '',
            'family_status' => '', 'wife_name' => '', 'husband_name' => '',
            'children_names' => ['', ' Anak Satu ', '  '], 'siblings_names' => [],
        ]))->assertCreated()->assertJsonPath('data.profile.nickname', null)
            ->assertJsonPath('data.profile.baptism_status', 'unknown')
            ->assertJsonPath('data.profile.baptism_church', null)
            ->assertJsonPath('data.profile.holy_spirit_baptism', null)
            ->assertJsonPath('data.profile.children_names', ['Anak Satu'])
            ->assertJsonPath('data.profile.siblings_names', []);
    }

    #[DataProvider('hiddenFamilyFields')]
    public function test_hidden_family_fields_are_neither_validated_nor_stored(string $status, string $field, mixed $invalid): void
    {
        $this->verifyAs();
        $this->withToken('token')->postJson('/api/v1/auth/register', array_merge($this->profile(), [
            'family_status' => $status, $field => $invalid,
        ]))->assertCreated()->assertJsonPath('data.profile.'.$field, $field === 'children_names' ? [] : null);
        $this->assertNull(Congregation::sole()->getRawOriginal($field));
    }

    public static function hiddenFamilyFields(): array
    {
        return [
            'head of family' => ['Kepala Keluarga', 'husband_name', ['invalid']],
            'wife' => ['Istri', 'wife_name', ['invalid']],
            'child' => ['Anak', 'children_names', [['invalid']]],
        ];
    }

    public function test_all_required_profile_fields_are_enforced(): void
    {
        $this->verifyAs();
        foreach (array_keys($this->profile()) as $field) {
            $payload = $this->profile();
            unset($payload[$field]);
            $this->withToken('token')->postJson('/api/v1/auth/register', $payload)
                ->assertUnprocessable()->assertJsonValidationErrors([$field]);
        }
        $this->assertDatabaseCount('congregations', 0);
        $this->assertDatabaseCount('mobile_accounts', 0);
    }

    #[DataProvider('invalidProfileFields')]
    public function test_extended_profile_validation_rejects_invalid_values(string $field, mixed $value, string $error): void
    {
        $this->verifyAs();
        $this->withToken('token')->postJson('/api/v1/auth/register', array_merge($this->profile(), [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors([$error]);
        $this->assertDatabaseCount('congregations', 0);
    }

    public static function invalidProfileFields(): array
    {
        return [
            'blood type' => ['blood_type', 'C', 'blood_type'],
            'education length' => ['last_education', str_repeat('x', 101), 'last_education'],
            'family status length' => ['family_status', str_repeat('x', 101), 'family_status'],
            'baptism church length' => ['baptism_church', str_repeat('x', 256), 'baptism_church'],
            'church origin length' => ['church_origin', str_repeat('x', 256), 'church_origin'],
            'reason length' => ['reason_to_move_church', str_repeat('x', 2001), 'reason_to_move_church'],
            'wife length' => ['wife_name', str_repeat('x', 256), 'wife_name'],
            'husband length' => ['husband_name', str_repeat('x', 256), 'husband_name'],
            'boolean' => ['holy_spirit_baptism', 'true', 'holy_spirit_baptism'],
            'children must be array' => ['children_names', 'Anak Satu', 'children_names'],
            'siblings must be list' => ['siblings_names', ['name' => 'Saudara'], 'siblings_names'],
            'child length' => ['children_names', [str_repeat('x', 256)], 'children_names.0'],
            'sibling type' => ['siblings_names', [123], 'siblings_names.0'],
            'birth date format' => ['date_of_birth', '20-06-1995', 'date_of_birth'],
            'phone type' => ['phone_number', 8123456789, 'phone_number'],
        ];
    }

    public function test_birth_and_baptism_dates_follow_the_registration_contract(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 12:00:00'));
        $this->verifyAs();
        foreach ([
            ['date_of_birth' => '2026-10-04'],
            ['baptism_status' => 'baptized', 'baptism_date' => '2026-10-05'],
            ['baptism_status' => 'baptized', 'baptism_date' => '04-10-2026'],
            ['baptism_status' => 'not_baptized', 'baptism_date' => '2020-01-01'],
        ] as $fields) {
            $error = isset($fields['date_of_birth']) ? 'date_of_birth' : 'baptism_date';
            $this->withToken('token')->postJson('/api/v1/auth/register', array_merge($this->profile(), $fields))
                ->assertUnprocessable()->assertJsonValidationErrors([$error]);
        }
        $this->withToken('token')->postJson('/api/v1/auth/register', array_merge($this->profile(), [
            'baptism_status' => 'baptized', 'baptism_date' => '2026-10-04',
        ]))->assertCreated();
    }

    public function test_profile_photo_rejects_non_jpeg_non_images_and_files_over_five_mib(): void
    {
        Storage::fake('public');
        $this->verifyAs();
        foreach ([
            UploadedFile::fake()->image('selfie.png'),
            UploadedFile::fake()->image('selfie.jpg')->size(5121),
            UploadedFile::fake()->createWithContent('selfie.jpg', 'not a JPEG')->mimeType('text/plain'),
        ] as $photo) {
            $this->withToken('token')->withHeader('Accept', 'application/json')
                ->post('/api/v1/auth/register', array_merge($this->profile(), ['profile_photo' => $photo]))
                ->assertUnprocessable()->assertJsonValidationErrors(['profile_photo']);
        }
        $this->assertDatabaseCount('congregations', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_photo_storage_failure_does_not_create_a_profile_and_allows_retry(): void
    {
        $this->verifyAs();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::set('public', $disk);
        $this->withToken('token')->withHeader('Accept', 'application/json')->post('/api/v1/auth/register', array_merge($this->profile(), [
            'profile_photo' => UploadedFile::fake()->image('selfie.jpg'),
        ]))->assertStatus(500);
        $this->assertDatabaseCount('congregations', 0);
        $this->assertDatabaseCount('mobile_accounts', 0);

        Storage::fake('public');
        $this->post('/api/v1/auth/register', array_merge($this->profile(), [
            'profile_photo' => UploadedFile::fake()->image('retry.jpg'),
        ]))->assertCreated();
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_repeated_registration_does_not_create_or_overwrite_records(): void
    {
        $this->verifyAs();
        $this->withToken('token')->postJson('/api/v1/auth/register', $this->profile())->assertCreated();
        $this->withToken('token')->postJson('/api/v1/auth/register', array_merge($this->profile(), ['full_name' => 'Overwrite', 'gender' => 'female']))
            ->assertConflict();
        $this->assertDatabaseCount('congregations', 1);
        $this->assertDatabaseCount('mobile_accounts', 1);
        $this->assertDatabaseHas('congregations', ['full_name' => 'Jemaat Baru']);
    }

    public function test_deleted_or_inactive_identities_cannot_register_again(): void
    {
        $congregation = Congregation::factory()->create(['legacy_firebase_uid' => 'blocked-uid', 'is_active' => false]);
        $this->verifyAs('blocked-uid');
        $this->withToken('token')->postJson('/api/v1/auth/register', $this->profile())->assertConflict();
        $congregation->delete();
        $this->withToken('token')->postJson('/api/v1/auth/register', $this->profile())->assertConflict();
        $this->assertDatabaseCount('congregations', 1);
        $this->assertDatabaseCount('mobile_accounts', 0);
    }

    public function test_existing_mobile_uid_cannot_be_registered_even_when_its_profile_has_another_uid(): void
    {
        $congregation = Congregation::factory()->create(['legacy_firebase_uid' => 'different-uid']);
        $account = MobileAccount::factory()->create(['congregation_id' => $congregation->id, 'firebase_uid' => 'blocked-uid']);
        $account->delete();
        $this->verifyAs('blocked-uid');
        $this->withToken('token')->postJson('/api/v1/auth/register', $this->profile())->assertConflict();
        $this->assertDatabaseCount('congregations', 1);
    }

    public function test_matching_email_does_not_link_a_different_firebase_identity(): void
    {
        $congregation = Congregation::factory()->create(['email' => 'existing@example.com', 'legacy_firebase_uid' => null]);
        $this->verifyAs('new-uid', 'existing@example.com');
        $this->withToken('token')->postJson('/api/v1/auth/register', $this->profile())->assertConflict();
        $this->assertNull($congregation->fresh()->legacy_firebase_uid);
        $this->assertDatabaseCount('congregations', 1);
        $this->assertDatabaseCount('mobile_accounts', 0);
    }

    public function test_registration_supports_firebase_identity_without_email(): void
    {
        $this->verifyAs('phone-uid', null, false);
        $this->withToken('token')->postJson('/api/v1/auth/register', $this->profile())->assertCreated()
            ->assertJsonPath('data.account.email', null)
            ->assertJsonPath('data.account.email_verified', false)
            ->assertJsonPath('data.profile.email', null);
    }

    public function test_registration_validates_profile_before_writing_data(): void
    {
        $this->verifyAs();
        $this->withToken('token')->postJson('/api/v1/auth/register', [
            'gender' => 'invalid', 'date_of_birth' => '2999-01-01', 'phone_number' => 'invalid',
            'baptism_status' => 'baptized',
        ])->assertUnprocessable()->assertJsonValidationErrors(['full_name', 'gender', 'date_of_birth', 'phone_number', 'baptism_date']);
        $this->assertDatabaseCount('congregations', 0);
        $this->assertDatabaseCount('mobile_accounts', 0);
    }

    public function test_registration_requires_verified_token_and_handles_auth_outage(): void
    {
        $this->postJson('/api/v1/auth/register', $this->profile())->assertUnauthorized();
        foreach ([new FirebaseTokenException('Invalid'), new FirebaseAuthUnavailableException('Unavailable')] as $exception) {
            $this->app->instance(FirebaseTokenVerifier::class, new class($exception) implements FirebaseTokenVerifier
            {
                public function __construct(private readonly \Exception $exception) {}

                public function verify(string $token): VerifiedFirebaseToken
                {
                    throw $this->exception;
                }
            });
            $this->withToken('unverified-token')->postJson('/api/v1/auth/register', $this->profile())
                ->assertStatus($exception instanceof FirebaseTokenException ? 401 : 503);
        }
        $this->assertDatabaseCount('congregations', 0);
        $this->assertDatabaseCount('mobile_accounts', 0);
    }

    public function test_account_insert_conflict_rolls_back_the_entire_registration(): void
    {
        Storage::fake('public');
        $this->verifyAs();
        MobileAccount::creating(function (): void {
            throw new UniqueConstraintViolationException(
                'sqlite', 'insert into mobile_accounts', [], new \PDOException('Unique constraint violation'),
            );
        });

        try {
            $this->withToken('token')->withHeader('Accept', 'application/json')->post('/api/v1/auth/register', array_merge($this->profile(), [
                'profile_photo' => UploadedFile::fake()->image('selfie.jpg'),
            ]))->assertConflict();
            $this->assertDatabaseCount('congregations', 0);
            $this->assertDatabaseCount('mobile_accounts', 0);
            $this->assertDatabaseCount('member_number_sequences', 0);
            $this->assertCount(0, Storage::disk('public')->allFiles());
        } finally {
            MobileAccount::flushEventListeners();
        }
    }

    private function profile(): array
    {
        return [
            'full_name' => 'Jemaat Baru', 'gender' => 'male', 'place_of_birth' => 'Jakarta',
            'date_of_birth' => '1995-06-20', 'phone_number' => '08123456789',
            'address' => 'Jalan Gereja 1', 'blood_type' => 'AB', 'last_education' => 'D1, D2, D3',
            'occupation' => 'Wiraswasta', 'marital_status' => 'married',
        ];
    }

    private function verifyAs(string $uid = 'new-uid', ?string $email = 'new@example.com', bool $emailVerified = true): void
    {
        $verified = new VerifiedFirebaseToken($uid, $email, $emailVerified, ['password'], CarbonImmutable::now(), []);
        $this->app->instance(FirebaseTokenVerifier::class, new class($verified) implements FirebaseTokenVerifier
        {
            public function __construct(private readonly VerifiedFirebaseToken $verified) {}

            public function verify(string $token): VerifiedFirebaseToken
            {
                return $this->verified;
            }
        });
    }
}

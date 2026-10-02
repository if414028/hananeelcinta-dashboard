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
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ])->postJson('/api/v1/auth/register', [
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
        ]);

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

    public function test_repeated_registration_does_not_create_or_overwrite_records(): void
    {
        $this->verifyAs();
        $this->withToken('token')->postJson('/api/v1/auth/register', $this->profile())->assertCreated();
        $this->withToken('token')->postJson('/api/v1/auth/register', ['full_name' => 'Overwrite', 'gender' => 'female'])
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
        $this->verifyAs();
        MobileAccount::creating(function (): void {
            throw new UniqueConstraintViolationException(
                'sqlite', 'insert into mobile_accounts', [], new \PDOException('Unique constraint violation'),
            );
        });

        try {
            $this->withToken('token')->postJson('/api/v1/auth/register', $this->profile())->assertConflict();
            $this->assertDatabaseCount('congregations', 0);
            $this->assertDatabaseCount('mobile_accounts', 0);
            $this->assertDatabaseCount('member_number_sequences', 0);
        } finally {
            MobileAccount::flushEventListeners();
        }
    }

    private function profile(): array
    {
        return ['full_name' => 'Jemaat Baru', 'gender' => 'male'];
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

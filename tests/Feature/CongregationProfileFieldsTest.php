<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\User;
use App\Services\FirebaseImportService;
use App\Support\CongregationProfileFields;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CongregationProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $this->actingAs($user);
    }

    public function test_legacy_notes_populate_separate_edit_fields_and_preserve_extra_notes(): void
    {
        $congregation = Congregation::factory()->create(['notes' => $this->legacyNotes()]);
        $response = $this->get(route('admin.congregations.edit', $congregation))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame('jemaat.lama', $xpath->evaluate('string(//input[@name="legacy_username"]/@value)'));
        $this->assertSame('SuperUser', $xpath->evaluate('string(//input[@name="legacy_role"]/@value)'));
        $this->assertTrue($xpath->evaluate('boolean(//input[@name="legacy_role"]/@readonly)'));
        $this->assertSame('O', $xpath->evaluate('string(//select[@name="blood_type"]/option[@selected]/@value)'));
        $this->assertSame('0', $xpath->evaluate('string(//select[@name="holy_spirit_baptism"]/option[@selected]/@value)'));
        $this->assertSame("Hana\nDaniel", trim($xpath->evaluate('string(//textarea[@name="children_names"])')));
        $this->assertSame('Hubungi sore hari.', trim($xpath->evaluate('string(//textarea[@name="notes"])')));
        $response->assertSee('Nama ayah')->assertSee('Nama ibu')->assertSee('Pendidikan terakhir');
        // Reading an edit form must not modify the stored record.
        $this->assertNull($congregation->fresh()->blood_type);
        $this->assertSame($this->legacyNotes(), $congregation->fresh()->notes);

        $this->get(route('admin.congregations.show', $congregation))->assertOk()
            ->assertSee('jemaat.lama')->assertSee('Maria')->assertSee('Hana, Daniel')->assertSee('Hubungi sore hari.');
    }

    public function test_admin_can_create_update_and_clear_profile_fields_without_changing_access(): void
    {
        $this->get(route('admin.congregations.create'))->assertOk()->assertSee('name="blood_type"', false)->assertSee('name="father_name"', false);
        $this->post(route('admin.congregations.store'), $this->payload() + $this->profilePayload() + ['legacy_role' => 'SuperUser'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $congregation = Congregation::query()->sole();
        $this->assertSame('AB', $congregation->blood_type);
        $this->assertSame('S1', $congregation->last_education);
        $this->assertSame(['Hana', 'Daniel'], $congregation->children_names);
        $this->assertSame(['Ruth'], $congregation->siblings_names);
        $this->assertFalse($congregation->holy_spirit_baptism);
        $this->assertNull($congregation->legacy_role);
        $this->assertSame('User', $congregation->mobileRole());

        $congregation->update(['legacy_role' => 'Jemaat']);
        $this->put(route('admin.congregations.update', $congregation), $this->payload() + array_replace($this->profilePayload(), [
            'blood_type' => 'O', 'mother_name' => 'Nama Ibu Baru', 'children_names' => '',
            'siblings_names' => [], 'holy_spirit_baptism' => '', 'legacy_role' => 'SuperUser',
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $congregation->refresh();
        $this->assertSame('O', $congregation->blood_type);
        $this->assertSame('Nama Ibu Baru', $congregation->mother_name);
        $this->assertNull($congregation->children_names);
        $this->assertSame([], $congregation->siblings_names);
        $this->assertNull($congregation->holy_spirit_baptism);
        $this->assertSame('Jemaat', $congregation->legacy_role);
    }

    public function test_profile_validation_rejects_bad_values_and_restores_name_lists(): void
    {
        $payload = $this->payload() + $this->profilePayload();
        $this->from(route('admin.congregations.create'))->post(route('admin.congregations.store'), array_replace($payload, [
            'blood_type' => 'C', 'holy_spirit_baptism' => 'invalid', 'father_name' => str_repeat('x', 256),
        ]))->assertSessionHasErrors(['blood_type', 'holy_spirit_baptism', 'father_name']);
        $response = $this->get(route('admin.congregations.create'))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(trim($this->profilePayload()['children_names']), trim($xpath->evaluate('string(//textarea[@name="children_names"])')));
        $this->assertDatabaseCount('congregations', 0);

        $this->post(route('admin.congregations.store'), array_replace($payload, ['children_names' => ['invalid' => 'Hana']]))
            ->assertSessionHasErrors('children_names');
    }

    public function test_migration_backfills_existing_and_deleted_records_without_overwriting_profile_or_notes(): void
    {
        $migration = require database_path('migrations/2026_10_17_010000_extract_congregation_profile_fields.php');
        $migration->down();
        $congregation = Congregation::factory()->create(['notes' => $this->legacyNotes()]);
        $other = Congregation::factory()->create(['notes' => 'Golongan darah: O', 'blood_type' => 'AB']);
        $congregation->delete();
        $migration->up();

        $congregation->refresh();
        $this->assertSame('jemaat.lama', $congregation->legacy_username);
        $this->assertSame('SuperUser', $congregation->legacy_role);
        $this->assertSame('O', $congregation->blood_type);
        $this->assertSame('Maria', $congregation->mother_name);
        $this->assertSame(['Hana', 'Daniel'], $congregation->children_names);
        $this->assertFalse($congregation->holy_spirit_baptism);
        $this->assertSame($this->legacyNotes(), $congregation->notes);
        $this->assertSame('AB', $other->fresh()->blood_type);
        $this->assertSame('Golongan darah: O', $other->fresh()->notes);
    }

    public function test_unrecognized_invalid_and_conflicting_notes_are_not_lost(): void
    {
        $result = CongregationProfileFields::extract("Golongan darah: C\nBaptisan Roh Kudus: mungkin\nNama ibu: Maria\nInfo lain: Tetap\nCatatan bebas", ['mother_name' => 'Ruth']);
        $this->assertArrayNotHasKey('blood_type', $result);
        $this->assertArrayNotHasKey('holy_spirit_baptism', $result);
        $this->assertArrayNotHasKey('mother_name', $result);
        $this->assertSame("Golongan darah: C\nBaptisan Roh Kudus: mungkin\nNama ibu: Maria\nInfo lain: Tetap\nCatatan bebas", $result['notes']);
    }

    public function test_future_firebase_imports_store_profile_values_in_columns(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'congregation-profile-');
        try {
            file_put_contents($path, json_encode(['users' => ['profile-import' => [
                'id' => 'profile-import', 'nij' => 'HC-IMPORT-PROFILE', 'fullName' => 'Jemaat Impor', 'gender' => 'Laki-laki',
                'username' => 'jemaat.impor', 'role' => 'SuperUser', 'bloodType' => 'A', 'lastEducation' => 'S1',
                'motherFullName' => 'Maria', 'childrenName' => 'Hana, Daniel', 'holySpiritBaptism' => '0',
            ]]], JSON_THROW_ON_ERROR));
            $result = app(FirebaseImportService::class)->import($path, ['only' => ['congregations']]);
            $this->assertSame(1, $result['totals']['inserted']);
            $congregation = Congregation::query()->sole();
            $this->assertSame('jemaat.impor', $congregation->legacy_username);
            $this->assertSame('A', $congregation->blood_type);
            $this->assertSame('Maria', $congregation->mother_name);
            $this->assertSame(['Hana', 'Daniel'], $congregation->children_names);
            $this->assertFalse($congregation->holy_spirit_baptism);
            $this->assertNull($congregation->notes);
            $this->assertSame('User', $congregation->mobileRole());
        } finally {
            unlink($path);
        }
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        return new \DOMXPath($document);
    }

    private function legacyNotes(): string
    {
        return "Username lama: jemaat.lama\nRole Firebase: SuperUser\nGolongan darah: O\nPendidikan terakhir: S1\nAsal gereja: Gereja Asal\nAlasan pindah gereja: Pindah domisili\nStatus dalam keluarga: Kepala Keluarga\nNama ayah: Yusuf\nNama ibu: Maria\nNama suami: Suami\nNama istri: Istri\nNama anak: Hana, Daniel\nNama saudara: Ruth\nBaptisan Roh Kudus: 0\nGereja baptisan air: Gereja Baptis\nHubungi sore hari.";
    }

    private function payload(): array
    {
        return ['full_name' => 'Jemaat Profil', 'gender' => 'male', 'baptism_status' => 'unknown', 'membership_status' => 'member', 'is_active' => '1'];
    }

    private function profilePayload(): array
    {
        return [
            'legacy_username' => 'jemaat.profil', 'blood_type' => 'AB', 'last_education' => 'S1',
            'church_origin' => 'Gereja Asal', 'reason_to_move_church' => 'Pindah domisili',
            'family_status' => 'Kepala Keluarga', 'father_name' => 'Yusuf', 'mother_name' => 'Maria',
            'wife_name' => 'Istri', 'husband_name' => '', 'children_names' => " Hana \n\nDaniel ",
            'siblings_names' => 'Ruth', 'holy_spirit_baptism' => '0', 'baptism_church' => 'Gereja Baptis',
            'notes' => 'Hubungi sore hari.',
        ];
    }
}

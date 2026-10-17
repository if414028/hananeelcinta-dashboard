<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\MobileAccount;
use App\Models\User;
use App\Services\ImportNewFirebaseCongregations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ImportNewFirebaseCongregationsTest extends TestCase
{
    use RefreshDatabase;

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    public function test_existing_profiles_and_accounts_remain_unchanged_even_when_new_names_match(): void
    {
        $old = Congregation::factory()->create(['legacy_firebase_uid' => 'old-frans', 'member_number' => 'HC-00194', 'full_name' => 'Frans Rudy Silaen', 'notes' => 'Catatan CMS dipertahankan.']);
        $mobile = MobileAccount::factory()->create(['congregation_id' => $old->id, 'firebase_uid' => 'old-frans']);
        $admin = User::factory()->create(['congregation_id' => $old->id]);
        $original = $old->refresh()->getRawOriginal();
        $originalMobile = $mobile->refresh()->getRawOriginal();
        $originalAdmin = $admin->refresh()->getRawOriginal();
        $file = $this->file([
            'old-frans' => $this->record('old-frans', 'HC-00194', 'Nama dari Firebase tidak dipakai'),
            'new-frans' => $this->record('new-frans', 'HC-00786', 'FRANS RUDY SILAEN'),
            'new-ria' => $this->record('new-ria', 'HC-00824', 'Ria Mustati'),
        ]);

        $report = app(ImportNewFirebaseCongregations::class)->handle($file, expected: 2);
        $this->assertSame(2, $report['inserted']);
        $this->assertSame(1, $report['existing']);
        $this->assertSame(0, $report['duplicate_new']);
        $this->assertSame($original, $old->fresh()->getRawOriginal());
        $this->assertSame($originalMobile, $mobile->fresh()->getRawOriginal());
        $this->assertSame($originalAdmin, $admin->fresh()->getRawOriginal());
        $this->assertDatabaseHas('congregations', ['member_number' => 'HC-00786', 'legacy_firebase_uid' => 'new-frans', 'deleted_at' => null]);
        $this->assertDatabaseCount('congregations', 3);
        $this->assertStringNotContainsString('never-store-this', Congregation::query()->where('legacy_firebase_uid', 'new-frans')->sole()->toJson());

        $again = app(ImportNewFirebaseCongregations::class)->handle($file);
        $this->assertSame(0, $again['inserted']);
        $this->assertSame(3, $again['existing']);
        $this->assertDatabaseCount('congregations', 3);
        $this->assertDatabaseCount('data_imports', 1);
    }

    public function test_only_duplicates_among_new_users_choose_highest_numeric_nij(): void
    {
        $file = $this->file([
            'lower' => $this->record('lower', 'HC-9', ' Nama   Sama '),
            'higher' => $this->record('higher', 'HC-10', 'NAMA SAMA'),
            'ria' => $this->record('ria', 'HC-00824', 'Ria Mustati'),
            'rasna' => $this->record('rasna', 'HC-00825', 'Rasnaharyani'),
        ], wrapped: true);
        $report = app(ImportNewFirebaseCongregations::class)->handle($file, expected: 3);

        $this->assertSame(3, $report['inserted']);
        $this->assertSame(1, $report['duplicate_new']);
        $this->assertSame('HC-10', $report['duplicates'][0]['keep']);
        $this->assertDatabaseHas('congregations', ['legacy_firebase_uid' => 'higher', 'member_number' => 'HC-10']);
        $this->assertDatabaseMissing('congregations', ['legacy_firebase_uid' => 'lower']);
        $this->assertDatabaseHas('congregations', ['full_name' => 'Ria Mustati']);
        $this->assertDatabaseHas('congregations', ['full_name' => 'Rasnaharyani']);
    }

    public function test_dry_run_does_not_write_records_or_import_logs(): void
    {
        $file = $this->file(['new' => $this->record('new', 'HC-00786', 'Jemaat Baru')]);
        $this->artisan('firebase:import-new-congregations', ['file' => $file, '--dry-run' => true, '--expected' => '1'])
            ->expectsOutputToContain('Simulasi selesai. Tidak ada perubahan database.')->assertSuccessful();
        $this->assertDatabaseCount('congregations', 0);
        $this->assertDatabaseCount('data_imports', 0);
    }

    public function test_an_import_failure_rolls_back_every_new_record_and_the_import_log(): void
    {
        $file = $this->file([
            'valid' => $this->record('valid', 'HC-00786', 'Jemaat Valid'),
            'bad' => array_replace($this->record('bad', 'HC-00787', 'Jemaat Invalid'), ['gender' => 'invalid']),
        ]);
        $this->artisan('firebase:import-new-congregations', ['file' => $file])
            ->expectsOutputToContain('Impor dibatalkan seluruhnya')->assertFailed();
        $this->assertDatabaseCount('congregations', 0);
        $this->assertDatabaseCount('data_imports', 0);
    }

    public function test_expected_count_and_nij_collisions_abort_without_changing_existing_data(): void
    {
        $old = Congregation::factory()->create(['member_number' => 'HC-00786']);
        $file = $this->file(['new' => $this->record('new', 'HC-00787', 'Jemaat Baru')]);
        $this->artisan('firebase:import-new-congregations', ['file' => $file, '--expected' => '50'])
            ->expectsOutputToContain('tidak sesuai --expected=50')->assertExitCode(2);
        $collision = $this->file(['new' => $this->record('new', 'HC-00786', 'Jemaat Baru')]);
        $this->artisan('firebase:import-new-congregations', ['file' => $collision])
            ->expectsOutputToContain('NIJ HC-00786 sudah dipakai')->assertExitCode(2);
        $this->assertDatabaseCount('congregations', 1);
        $this->assertDatabaseCount('data_imports', 0);
        $this->assertFalse($old->fresh()->trashed());
    }

    public function test_existing_archived_identities_are_not_revived_and_deleted_export_rows_are_skipped(): void
    {
        $old = Congregation::factory()->create(['legacy_firebase_uid' => 'archived', 'member_number' => 'HC-00194']);
        $old->delete();
        $file = $this->file([
            'archived' => $this->record('archived', 'HC-00194', 'Tidak dipulihkan'),
            'deleted-source' => $this->record('deleted-source', 'HC-00786', 'Dihapus di Firebase') + ['deletedAt' => '2026-10-01'],
        ]);
        $report = app(ImportNewFirebaseCongregations::class)->handle($file);
        $this->assertSame(1, $report['existing']);
        $this->assertSame(1, $report['deleted_in_export']);
        $this->assertSame(0, $report['inserted']);
        $this->assertSoftDeleted($old);
        $this->assertDatabaseCount('congregations', 1);
        $this->assertDatabaseCount('data_imports', 0);
    }

    public function test_conflicting_numbers_in_the_new_batch_are_rejected_before_any_write(): void
    {
        $file = $this->file([
            'first' => $this->record('first', 'HC-00786', 'Nama Satu'),
            'second' => $this->record('second', 'HC-00786', 'Nama Dua'),
        ]);
        $this->artisan('firebase:import-new-congregations', ['file' => $file])
            ->expectsOutputToContain('NIJ HC-00786 sudah dipakai')->assertExitCode(2);
        $this->assertDatabaseCount('congregations', 0);
        $this->assertDatabaseCount('data_imports', 0);
    }

    private function file(array $users, bool $wrapped = false): string
    {
        $file = tempnam(sys_get_temp_dir(), 'new-firebase-test-');
        file_put_contents($file, json_encode($wrapped ? ['users' => $users] : $users, JSON_THROW_ON_ERROR));
        $this->files[] = $file;

        return $file;
    }

    private function record(string $uid, string $nij, string $name): array
    {
        return ['id' => $uid, 'nij' => $nij, 'fullName' => $name, 'gender' => 'Laki-laki', 'phoneNumber' => '08123456789', 'password' => 'never-store-this', 'fcmToken' => 'never-store-this'];
    }
}

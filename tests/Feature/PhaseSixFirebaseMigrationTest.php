<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DataImportStatus;
use App\Enums\DayOfWeek;
use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestSource;
use App\Enums\PrayerRequestStatus;
use App\Models\Announcement;
use App\Models\Congregation;
use App\Models\DataImport;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Models\PrayerRequest;
use App\Services\FirebaseImageMigrationService;
use App\Services\FirebaseImportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PhaseSixFirebaseMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function test_firebase_json_imports_all_modules_converts_timestamps_and_logs_errors(): void
    {
        $timestamp = 1_697_045_968_836;
        $file = $this->jsonFile([
            'announcements' => [
                'announcement-key' => ['title' => 'Join Our Media Team', 'desc' => 'Isi pengumuman', 'date' => $timestamp, 'imageUrl' => 'https://firebasestorage.googleapis.com/image.jpg', 'contactPerson' => '0812-3456-7890', 'contactPersonName' => 'PIC', 'infoUrl' => 'https://example.com/info'],
                'invalid-announcement' => ['desc' => 'Tanpa judul'],
            ],
            'mk' => [null, ['contact' => '+62 813 17183 119', 'day' => 'Selasa', 'desc' => 'Bapak - Bapak', 'location' => 'Hancin', 'pic' => 'Pak Rendi', 'time' => '17:30 WIB']],
            'pastorMessages' => [
                'message-key' => ['title' => 'Judul renungan', 'messages' => '<p>Isi renungan</p><script>alert(1)</script>', 'writer' => 'Gembala', 'date' => $timestamp],
            ],
        ]);

        $report = app(FirebaseImportService::class)->import($file, ['chunk' => 1]);

        $expectedDate = CarbonImmutable::createFromTimestampMs($timestamp, config('app.timezone'))->format('Y-m-d H:i:s');
        $announcement = Announcement::query()->where('legacy_firebase_key', 'announcement-key')->firstOrFail();
        $altar = FamilyAltar::query()->where('legacy_firebase_index', 1)->firstOrFail();
        $message = PastorMessage::query()->where('legacy_firebase_key', 'message-key')->firstOrFail();

        $this->assertSame($expectedDate, $announcement->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('+6281234567890', $announcement->contact_person_phone);
        $this->assertSame('https://firebasestorage.googleapis.com/image.jpg', $announcement->legacy_image_url);
        $this->assertSame(DayOfWeek::Tuesday, $altar->day_of_week);
        $this->assertSame('17:30:00', $altar->start_time);
        $this->assertSame(1, $altar->sort_order);
        $this->assertStringNotContainsString('script', $message->content);
        $this->assertSame(5, $report['totals']['total']);
        $this->assertSame(3, $report['totals']['inserted']);
        $this->assertSame(1, $report['totals']['skipped']);
        $this->assertSame(1, $report['totals']['failed']);

        $log = DataImport::query()->sole();
        $this->assertSame(DataImportStatus::CompletedWithErrors, $log->status);
        $this->assertSame(5, $log->total_records);
        $this->assertSame(3, $log->inserted_records);
        $this->assertStringContainsString('invalid-announcement', (string) $log->error_summary);
    }

    public function test_dry_run_and_module_filter_do_not_write_database_or_import_log(): void
    {
        $file = $this->jsonFile([
            'announcements' => ['a1' => ['title' => 'A1', 'desc' => 'Description', 'date' => 1_700_000_000_000]],
            'mk' => [null, ['location' => 'Tidak boleh masuk', 'day' => 'Senin']],
        ]);

        $report = app(FirebaseImportService::class)->import($file, ['dry_run' => true, 'only' => ['announcements']]);

        $this->assertTrue($report['dry_run']);
        $this->assertSame(1, $report['totals']['inserted']);
        $this->assertDatabaseCount('announcements', 0);
        $this->assertDatabaseCount('family_altars', 0);
        $this->assertDatabaseCount('data_imports', 0);
    }

    public function test_pastor_message_with_empty_writer_uses_transparent_fallback(): void
    {
        $file = $this->jsonFile([
            'pastorMessages' => [
                'missing-writer' => [
                    'title' => 'Pesan tanpa nama penulis',
                    'messages' => 'Isi pesan',
                    'writer' => '',
                    'date' => 1_700_000_000_000,
                ],
            ],
        ]);

        $report = app(FirebaseImportService::class)->import($file, ['only' => ['pastor-messages']]);

        $this->assertSame('Penulis tidak tercantum', PastorMessage::query()->sole()->writer);
        $this->assertSame(1, $report['totals']['inserted']);
        $this->assertSame(0, $report['totals']['failed']);
        $this->assertStringContainsString('writer kosong', implode('\n', $report['warnings']));
    }

    public function test_firebase_users_import_as_idempotent_congregations_without_credentials(): void
    {
        $file = $this->jsonFile([
            'users' => [
                'firebase-uid' => [
                    'id' => 'firebase-uid',
                    'nij' => 'HC-00123',
                    'fullName' => 'Jemaat Firebase',
                    'gender' => 'Perempuan',
                    'dateOfBirth' => '04 July 1961',
                    'married' => 1,
                    'phoneNumber' => '0812-3456-7890',
                    'email' => 'JEMAAT@example.com',
                    'address' => 'Jakarta',
                    'job' => 'Guru',
                    'waterBaptism' => 1,
                    'waterBaptisteryDate' => '04 July 2018',
                    'photoImageUrl' => 'https://example.com/photo.jpg',
                    'password' => 'tidak-boleh-disimpan',
                    'fcmToken' => 'tidak-boleh-disimpan',
                ],
            ],
        ]);

        $service = app(FirebaseImportService::class);
        $service->import($file, ['only' => ['congregations']]);
        $rerun = $service->import($file, ['only' => ['congregations']]);
        $congregation = Congregation::query()->sole();

        $this->assertSame('firebase-uid', $congregation->legacy_firebase_uid);
        $this->assertSame('HC-00123', $congregation->member_number);
        $this->assertSame('female', $congregation->gender->value);
        $this->assertSame('1961-07-04', $congregation->date_of_birth->format('Y-m-d'));
        $this->assertSame('+6281234567890', $congregation->phone_number);
        $this->assertSame('jemaat@example.com', $congregation->email);
        $this->assertSame('https://example.com/photo.jpg', $congregation->legacy_profile_photo_url);
        $this->assertStringNotContainsString('tidak-boleh-disimpan', (string) $congregation->notes);
        $this->assertSame(1, $rerun['totals']['skipped']);
        $this->assertDatabaseCount('congregations', 1);
    }

    public function test_firebase_prayer_requests_import_with_congregation_contact_and_legacy_status(): void
    {
        Congregation::factory()->create([
            'legacy_firebase_uid' => 'requester-uid',
            'full_name' => 'Nama Jemaat',
            'email' => 'jemaat@example.com',
            'phone_number' => '+628123456789',
        ]);
        $file = $this->jsonFile([
            'prayerRequest' => [
                '1726386525820' => [
                    'id' => 1_726_386_525_820,
                    'prayDesc' => 'Mohon dukungan doa',
                    'prayResult' => '',
                    'prayType' => 'Kunjungan',
                    'requesterId' => 'requester-uid',
                    'requesterName' => 'Nama Lama',
                    'handlerId' => 'handler-uid',
                    'handlerName' => 'Pelayan Lama',
                    'status' => 'IN_PROGRESS',
                ],
            ],
        ]);

        $service = app(FirebaseImportService::class);
        $service->import($file, ['only' => ['prayer-requests']]);
        $rerun = $service->import($file, ['only' => ['prayer-requests']]);
        $prayer = PrayerRequest::query()->sole();

        $this->assertSame('1726386525820', $prayer->legacy_firebase_key);
        $this->assertSame('PR-FB-1726386525820', $prayer->reference_number);
        $this->assertSame('Nama Lama', $prayer->name);
        $this->assertSame('jemaat@example.com', $prayer->email);
        $this->assertSame('+628123456789', $prayer->phone_number);
        $this->assertSame(PrayerRequestCategory::Ministry, $prayer->prayer_category);
        $this->assertSame(PrayerRequestStatus::InPrayer, $prayer->status);
        $this->assertSame(PrayerRequestSource::Migration, $prayer->source);
        $this->assertTrue($prayer->is_confidential);
        $this->assertStringContainsString('Pelayan Lama', (string) $prayer->admin_notes);
        $this->assertSame(1, $rerun['totals']['skipped']);
    }

    public function test_import_is_idempotent_and_force_updates_existing_legacy_record(): void
    {
        $service = app(FirebaseImportService::class);
        $first = $this->jsonFile(['announcements' => ['same-key' => ['title' => 'Judul Lama', 'desc' => 'Isi', 'date' => 1_700_000_000_000]]]);
        $second = $this->jsonFile(['announcements' => ['same-key' => ['title' => 'Judul Baru', 'desc' => 'Isi baru', 'date' => 1_700_000_000_000]]]);

        $service->import($first, ['only' => ['announcements']]);
        $skipped = $service->import($second, ['only' => ['announcements']]);

        $this->assertSame(1, $skipped['totals']['skipped']);
        $this->assertSame('Judul Lama', Announcement::query()->sole()->title);
        $this->assertDatabaseCount('announcements', 1);

        $updated = $service->import($second, ['only' => ['announcements'], 'force' => true]);
        $this->assertSame(1, $updated['totals']['updated']);
        $this->assertSame('Judul Baru', Announcement::query()->sole()->title);
        $this->assertSame('judul-baru', Announcement::query()->sole()->slug);
        $this->assertDatabaseCount('announcements', 1);
    }

    public function test_invalid_json_creates_failed_import_log(): void
    {
        $file = $this->rawFile('{invalid-json');

        try {
            app(FirebaseImportService::class)->import($file);
            $this->fail('Invalid JSON seharusnya melempar exception.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('tidak valid', $exception->getMessage());
        }

        $log = DataImport::query()->sole();
        $this->assertSame(DataImportStatus::Failed, $log->status);
        $this->assertNotNull($log->finished_at);
    }

    public function test_artisan_command_supports_only_and_dry_run_options(): void
    {
        $file = $this->jsonFile([
            'announcements' => ['a1' => ['title' => 'Command Test', 'desc' => 'Description', 'date' => 1_700_000_000_000]],
            'mk' => [null, ['location' => 'Ignored', 'day' => 'Rabu']],
        ]);

        $this->artisan('firebase:import', ['file' => $file, '--dry-run' => true, '--only' => 'announcements', '--chunk' => 1])
            ->expectsOutputToContain('Firebase dry-run completed.')
            ->assertSuccessful();

        $this->assertDatabaseCount('announcements', 0);
        $this->assertDatabaseCount('family_altars', 0);
    }

    public function test_optional_image_migration_validates_and_stores_remote_image(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z4S8AAAAASUVORK5CYII=', true);
        $this->assertIsString($png);
        Http::fake(['https://firebasestorage.googleapis.com/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);
        $announcement = Announcement::factory()->create(['image' => null, 'legacy_image_url' => 'https://firebasestorage.googleapis.com/v0/b/example/image.png']);

        $report = app(FirebaseImageMigrationService::class)->migrate(['chunk' => 1, 'timeout' => 2, 'retries' => 1]);

        $announcement->refresh();
        $this->assertSame(1, $report['migrated']);
        $this->assertSame(0, $report['failed']);
        $this->assertNotNull($announcement->image);
        Storage::disk('public')->assertExists($announcement->image);
        $this->assertSame('https://firebasestorage.googleapis.com/v0/b/example/image.png', $announcement->legacy_image_url);
        $this->assertSame(DataImportStatus::Completed, DataImport::query()->sole()->status);
    }

    public function test_image_migration_dry_run_does_not_download_or_write(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        $announcement = Announcement::factory()->create(['image' => null, 'legacy_image_url' => 'https://example.com/image.webp']);

        $report = app(FirebaseImageMigrationService::class)->migrate(['dry_run' => true]);

        $this->assertSame(1, $report['migrated']);
        $this->assertNull($announcement->fresh()->image);
        $this->assertDatabaseCount('data_imports', 0);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    private function jsonFile(array $data): string
    {
        return $this->rawFile((string) json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function rawFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'firebase-test-');
        if ($file === false) {
            throw new \RuntimeException('Temporary file gagal dibuat.');
        }
        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;

        return $file;
    }
}

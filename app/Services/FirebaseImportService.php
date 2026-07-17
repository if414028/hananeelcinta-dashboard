<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AnnouncementStatus;
use App\Enums\BaptismStatus;
use App\Enums\CongregationGender;
use App\Enums\CongregationMaritalStatus;
use App\Enums\CongregationMembershipStatus;
use App\Enums\DataImportStatus;
use App\Enums\DayOfWeek;
use App\Enums\PastorMessageStatus;
use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestSource;
use App\Enums\PrayerRequestStatus;
use App\Models\Announcement;
use App\Models\Congregation;
use App\Models\DataImport;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Models\PrayerRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

final class FirebaseImportService
{
    /** @var list<string> */
    public const MODULES = ['congregations', 'prayer-requests', 'announcements', 'family-altars', 'pastor-messages'];

    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /**
     * @param  array{dry_run?: bool, force?: bool, only?: list<string>, chunk?: int}  $options
     * @param  null|callable(string, int, int): void  $progress
     * @return array<string, mixed>
     */
    public function import(string $filename, array $options = [], ?callable $progress = null): array
    {
        $path = $this->resolvePath($filename);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $force = (bool) ($options['force'] ?? false);
        $chunkSize = max(1, min(1000, (int) ($options['chunk'] ?? config('firebase.import.chunk_size', 100))));
        $modules = $this->normalizeModules($options['only'] ?? self::MODULES);
        $checksum = hash_file('sha256', $path);

        if ($checksum === false) {
            throw new RuntimeException('Checksum file tidak dapat dihitung.');
        }

        $log = $dryRun ? null : DataImport::query()->create([
            'type' => 'firebase',
            'filename' => basename($path),
            'checksum' => $checksum,
            'status' => DataImportStatus::Processing,
            'started_at' => now(),
        ]);

        try {
            $root = $this->readJson($path);
            $report = [
                'dry_run' => $dryRun,
                'force' => $force,
                'filename' => $path,
                'checksum' => $checksum,
                'import_id' => $log?->id,
                'modules' => [],
                'totals' => $this->emptyCounts(),
                'warnings' => [],
            ];
            $foundModules = 0;

            foreach ($modules as $module) {
                $node = $this->findNode($root, $module);
                if ($node === null) {
                    $report['modules'][$module] = $this->emptyCounts();
                    $report['warnings'][] = "Node Firebase untuk {$module} tidak ditemukan.";

                    continue;
                }

                $foundModules++;
                $moduleReport = $this->importModule($module, $node, $dryRun, $force, $chunkSize, $progress);
                $report['modules'][$module] = $moduleReport;
                $report['warnings'] = [...$report['warnings'], ...$moduleReport['warnings']];
                foreach (array_keys($this->emptyCounts()) as $key) {
                    $report['totals'][$key] += $moduleReport[$key];
                }
            }

            if ($foundModules === 0) {
                throw new InvalidArgumentException('JSON tidak memiliki node Firebase yang didukung untuk module terpilih.');
            }

            if ($log !== null) {
                $log->update([
                    'status' => $report['totals']['failed'] > 0 ? DataImportStatus::CompletedWithErrors : DataImportStatus::Completed,
                    'total_records' => $report['totals']['total'],
                    'inserted_records' => $report['totals']['inserted'],
                    'updated_records' => $report['totals']['updated'],
                    'skipped_records' => $report['totals']['skipped'],
                    'failed_records' => $report['totals']['failed'],
                    'finished_at' => now(),
                    'error_summary' => $this->encodeIssues($report['warnings']),
                ]);
            }

            return $report;
        } catch (Throwable $exception) {
            if ($log !== null) {
                $log->update([
                    'status' => DataImportStatus::Failed,
                    'finished_at' => now(),
                    'error_summary' => $exception->getMessage(),
                ]);
            }

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    private function readJson(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false || trim($contents) === '') {
            throw new InvalidArgumentException('File JSON kosong atau tidak dapat dibaca.');
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('JSON Firebase tidak valid: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException('Root JSON Firebase harus berupa object atau array.');
        }

        return $data;
    }

    /** @return array<mixed>|null */
    private function findNode(array $root, string $module): ?array
    {
        foreach ((array) config("firebase.nodes.{$module}", []) as $key) {
            if (array_key_exists($key, $root) && is_array($root[$key])) {
                return $root[$key];
            }
        }

        return null;
    }

    /** @return array{total:int,inserted:int,updated:int,skipped:int,failed:int,warnings:list<string>} */
    private function importModule(string $module, array $node, bool $dryRun, bool $force, int $chunkSize, ?callable $progress): array
    {
        $report = [...$this->emptyCounts(), 'warnings' => []];
        $report['total'] = count($node);
        $processed = 0;
        $progress?->__invoke($module, 0, $report['total']);

        foreach (array_chunk($node, $chunkSize, true) as $chunk) {
            $runner = function () use ($module, $chunk, $dryRun, $force, &$report, &$processed, $progress): void {
                foreach ($chunk as $legacyKey => $record) {
                    if ($record === null) {
                        $report['skipped']++;
                        $report['warnings'][] = "{$module}[{$legacyKey}] bernilai null dan diabaikan.";
                    } elseif (! is_array($record)) {
                        $report['failed']++;
                        $report['warnings'][] = "{$module}[{$legacyKey}] bukan object yang valid.";
                    } else {
                        try {
                            $result = $this->importRecord($module, (string) $legacyKey, $record, $dryRun, $force, $report['warnings']);
                            $report[$result]++;
                        } catch (Throwable $exception) {
                            $report['failed']++;
                            $report['warnings'][] = "{$module}[{$legacyKey}]: {$exception->getMessage()}";
                        }
                    }

                    $processed++;
                    $progress?->__invoke($module, $processed, $report['total']);
                }
            };

            $dryRun ? $runner() : DB::transaction($runner, 3);
        }

        return $report;
    }

    /** @param list<string> $warnings */
    private function importRecord(string $module, string $legacyKey, array $record, bool $dryRun, bool $force, array &$warnings): string
    {
        return match ($module) {
            'congregations' => $this->importCongregation($legacyKey, $record, $dryRun, $force, $warnings),
            'prayer-requests' => $this->importPrayerRequest($legacyKey, $record, $dryRun, $force, $warnings),
            'announcements' => $this->importAnnouncement($legacyKey, $record, $dryRun, $force, $warnings),
            'family-altars' => $this->importFamilyAltar($legacyKey, $record, $dryRun, $force, $warnings),
            'pastor-messages' => $this->importPastorMessage($legacyKey, $record, $dryRun, $force, $warnings),
            default => throw new InvalidArgumentException("Module {$module} tidak didukung."),
        };
    }

    /** @param list<string> $warnings */
    private function importCongregation(string $legacyKey, array $record, bool $dryRun, bool $force, array &$warnings): string
    {
        $firebaseUid = $this->nullableScalar($record['id'] ?? null) ?? $legacyKey;
        $existing = Congregation::withTrashed()->where('legacy_firebase_uid', $firebaseUid)->first();
        if ($existing !== null && ! $force) {
            return 'skipped';
        }

        $fullName = $this->requiredString($record, 'fullName');
        $memberNumber = $this->uniqueMemberNumber($this->requiredString($record, 'nij'), $existing?->id, $firebaseUid, $warnings);
        $email = $this->uniqueCongregationEmail($this->nullableScalar($record['email'] ?? null), $existing?->id, $firebaseUid, $warnings);
        $deleted = $this->nullableScalar($record['deletedAt'] ?? null) !== null;
        $waterBaptized = $this->truthy($record['waterBaptism'] ?? null);
        $data = [
            'legacy_firebase_uid' => $firebaseUid,
            'member_number' => $memberNumber,
            'full_name' => $fullName,
            'nickname' => null,
            'gender' => $this->congregationGender($this->requiredString($record, 'gender')),
            'place_of_birth' => $this->nullableScalar($record['placeOfBirth'] ?? null),
            'date_of_birth' => $this->calendarDate($record['dateOfBirth'] ?? null, "congregations[{$legacyKey}].dateOfBirth", $warnings),
            'marital_status' => $this->congregationMaritalStatus($record),
            'phone_number' => $this->phone($record['phoneNumber'] ?? null),
            'whatsapp_number' => $this->phone($record['phoneNumber'] ?? null),
            'email' => $email,
            'address' => $this->nullableScalar($record['address'] ?? null),
            'city' => null,
            'province' => null,
            'postal_code' => null,
            'occupation' => $this->nullableScalar($record['job'] ?? null),
            'baptism_status' => $waterBaptized ? BaptismStatus::Baptized : BaptismStatus::NotBaptized,
            'baptism_date' => $this->calendarDate($record['waterBaptisteryDate'] ?? null, "congregations[{$legacyKey}].waterBaptisteryDate", $warnings),
            'membership_status' => $deleted ? CongregationMembershipStatus::Inactive : CongregationMembershipStatus::Member,
            'joined_at' => null,
            'notes' => $this->congregationNotes($record),
            'profile_photo' => $existing?->profile_photo,
            'legacy_profile_photo_url' => $this->firebaseProfileUrl($record['photoImageUrl'] ?? null, "congregations[{$legacyKey}].photoImageUrl", $warnings),
            'is_active' => ! $deleted,
        ];

        return $this->persist($existing, Congregation::class, $data, $dryRun);
    }

    /** @param list<string> $warnings */
    private function importPrayerRequest(string $legacyKey, array $record, bool $dryRun, bool $force, array &$warnings): string
    {
        $existing = PrayerRequest::withTrashed()->where('legacy_firebase_key', $legacyKey)->first();
        if ($existing !== null && ! $force) {
            return 'skipped';
        }

        $requesterUid = $this->nullableScalar($record['requesterId'] ?? null);
        $congregation = $requesterUid ? Congregation::query()->where('legacy_firebase_uid', $requesterUid)->first() : null;
        $name = $this->nullableScalar($record['requesterName'] ?? null) ?? $congregation?->full_name ?? 'Nama tidak tercantum';
        $content = $this->nullableScalar($record['prayDesc'] ?? null);
        if ($content === null) {
            $content = 'Isi permohonan doa tidak dicantumkan.';
            $warnings[] = "prayer-requests[{$legacyKey}].prayDesc kosong; menggunakan keterangan pengganti.";
        }
        $createdAt = $this->timestamp($record['id'] ?? $legacyKey, "prayer-requests[{$legacyKey}].id", $warnings);
        $status = $this->prayerRequestStatus($record['status'] ?? null);
        $data = [
            'legacy_firebase_key' => $legacyKey,
            'reference_number' => $this->uniquePrayerReference($legacyKey, $existing?->id),
            'name' => $name,
            'email' => $congregation?->email,
            'phone_number' => $congregation?->phone_number,
            'prayer_category' => $this->prayerRequestCategory($record['prayType'] ?? null),
            'prayer_content' => $content,
            'is_anonymous' => false,
            'is_confidential' => true,
            'status' => $status,
            'admin_notes' => $this->prayerRequestNotes($record),
            'handled_by' => null,
            'handled_at' => null,
            'source' => PrayerRequestSource::Migration,
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ];

        return $this->persist($existing, PrayerRequest::class, $data, $dryRun);
    }

    /** @param list<string> $warnings */
    private function importAnnouncement(string $legacyKey, array $record, bool $dryRun, bool $force, array &$warnings): string
    {
        $title = $this->requiredString($record, 'title');
        $existing = Announcement::withTrashed()->where('legacy_firebase_key', $legacyKey)->first();
        if ($existing !== null && ! $force) {
            return 'skipped';
        }

        $publishedAt = $this->timestamp($record['date'] ?? null, "announcements[{$legacyKey}].date", $warnings);
        $description = $this->nullableScalar($this->field($record, 'desc', "announcements[{$legacyKey}].desc", $warnings));
        if ($description === null && array_key_exists('desc', $record)) {
            $warnings[] = "announcements[{$legacyKey}].desc kosong; description disimpan sebagai string kosong.";
        }
        $data = [
            'legacy_firebase_key' => $legacyKey,
            'title' => $title,
            'slug' => $this->uniqueSlug(Announcement::class, $title, $existing?->id),
            'excerpt' => Str::limit(strip_tags($description ?? ''), 240) ?: null,
            'description' => $description ?? '',
            'legacy_image_url' => $this->validUrl($this->field($record, 'imageUrl', "announcements[{$legacyKey}].imageUrl", $warnings), "announcements[{$legacyKey}].imageUrl", $warnings),
            'contact_person_phone' => $this->phone($this->field($record, 'contactPerson', "announcements[{$legacyKey}].contactPerson", $warnings)),
            'contact_person_name' => $this->nullableScalar($this->field($record, 'contactPersonName', "announcements[{$legacyKey}].contactPersonName", $warnings)),
            'information_url' => $this->validUrl($this->field($record, 'infoUrl', "announcements[{$legacyKey}].infoUrl", $warnings), "announcements[{$legacyKey}].infoUrl", $warnings),
            'published_at' => $publishedAt,
            'status' => $publishedAt ? AnnouncementStatus::Published : AnnouncementStatus::Draft,
            'is_featured' => false,
            'sort_order' => 0,
        ];

        return $this->persist($existing, Announcement::class, $data, $dryRun);
    }

    /** @param list<string> $warnings */
    private function importFamilyAltar(string $legacyKey, array $record, bool $dryRun, bool $force, array &$warnings): string
    {
        if (! ctype_digit($legacyKey)) {
            throw new InvalidArgumentException('Index Firebase harus berupa angka.');
        }
        $index = (int) $legacyKey;
        $existing = FamilyAltar::withTrashed()->where('legacy_firebase_index', $index)->first();
        if ($existing !== null && ! $force) {
            return 'skipped';
        }

        $location = $this->requiredString($record, 'location');
        $day = $this->day($this->requiredString($record, 'day'));
        $data = [
            'legacy_firebase_index' => $index,
            'name' => $location,
            'location_name' => $location,
            'description' => $this->nullableScalar($this->field($record, 'desc', "family-altars[{$legacyKey}].desc", $warnings)),
            'day_of_week' => $day,
            'start_time' => $this->time($this->field($record, 'time', "family-altars[{$legacyKey}].time", $warnings), "family-altars[{$legacyKey}].time", $warnings),
            'pic_name' => $this->nullableScalar($this->field($record, 'pic', "family-altars[{$legacyKey}].pic", $warnings)),
            'contact_phone' => $this->phone($this->field($record, 'contact', "family-altars[{$legacyKey}].contact", $warnings)),
            'is_active' => true,
            'sort_order' => $index,
        ];

        return $this->persist($existing, FamilyAltar::class, $data, $dryRun);
    }

    /** @param list<string> $warnings */
    private function importPastorMessage(string $legacyKey, array $record, bool $dryRun, bool $force, array &$warnings): string
    {
        $title = $this->requiredString($record, 'title');
        $content = $this->requiredString($record, 'messages');
        $writer = $this->nullableScalar($record['writer'] ?? null);
        if ($writer === null) {
            $writer = (string) config('firebase.import.missing_writer', 'Penulis tidak tercantum');
            $warnings[] = "pastor-messages[{$legacyKey}].writer kosong; menggunakan '{$writer}'.";
        }
        $existing = PastorMessage::withTrashed()->where('legacy_firebase_key', $legacyKey)->first();
        if ($existing !== null && ! $force) {
            return 'skipped';
        }

        $publishedAt = $this->timestamp($record['date'] ?? null, "pastor-messages[{$legacyKey}].date", $warnings);
        $content = $this->sanitizer->sanitize($content);
        $data = [
            'legacy_firebase_key' => $legacyKey,
            'title' => $title,
            'slug' => $this->uniqueSlug(PastorMessage::class, $title, $existing?->id),
            'writer' => $writer,
            'content' => $content,
            'excerpt' => Str::limit(strip_tags($content), 240),
            'published_at' => $publishedAt,
            'status' => $publishedAt ? PastorMessageStatus::Published : PastorMessageStatus::Draft,
            'is_featured' => false,
            'view_count' => $existing?->view_count ?? 0,
        ];

        return $this->persist($existing, PastorMessage::class, $data, $dryRun);
    }

    /** @param class-string<Model> $modelClass */
    private function persist(?Model $existing, string $modelClass, array $data, bool $dryRun): string
    {
        if ($dryRun) {
            return $existing === null ? 'inserted' : 'updated';
        }

        Model::withoutEvents(function () use ($existing, $modelClass, $data): void {
            if ($existing !== null) {
                if (method_exists($existing, 'trashed') && $existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($data);
            } else {
                $modelClass::query()->create($data);
            }
        });

        return $existing === null ? 'inserted' : 'updated';
    }

    /** @param class-string<Model> $modelClass */
    private function uniqueSlug(string $modelClass, string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title) ?: 'firebase-content';
        $slug = $base;
        $suffix = 2;
        while ($modelClass::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /** @param list<string> $warnings */
    private function uniqueMemberNumber(string $value, ?int $ignoreId, string $firebaseUid, array &$warnings): string
    {
        $base = Str::upper(trim($value));
        $candidate = $base;
        $suffix = 2;
        while (Congregation::withTrashed()->where('member_number', $candidate)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $candidate = $base.'-'.$suffix++;
        }
        if ($candidate !== $base) {
            $warnings[] = "congregations[{$firebaseUid}].nij '{$base}' duplikat; disimpan sebagai '{$candidate}'.";
        }

        return $candidate;
    }

    private function uniquePrayerReference(string $legacyKey, ?int $ignoreId): string
    {
        $base = 'PR-FB-'.$legacyKey;
        $candidate = $base;
        $suffix = 2;
        while (PrayerRequest::withTrashed()->where('reference_number', $candidate)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    /** @param list<string> $warnings */
    private function uniqueCongregationEmail(?string $email, ?int $ignoreId, string $firebaseUid, array &$warnings): ?string
    {
        if ($email === null) {
            return null;
        }
        $email = Str::lower($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $warnings[] = "congregations[{$firebaseUid}].email tidak valid dan diubah menjadi null.";

            return null;
        }
        if (Congregation::withTrashed()->where('email', $email)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $warnings[] = "congregations[{$firebaseUid}].email '{$email}' duplikat dan diubah menjadi null.";

            return null;
        }

        return $email;
    }

    private function congregationGender(string $value): CongregationGender
    {
        return match (Str::lower($value)) {
            'laki-laki', 'laki laki', 'male', 'pria' => CongregationGender::Male,
            'perempuan', 'female', 'wanita' => CongregationGender::Female,
            default => throw new InvalidArgumentException("Gender '{$value}' tidak dikenali."),
        };
    }

    private function congregationMaritalStatus(array $record): CongregationMaritalStatus
    {
        $familyStatus = Str::lower($this->nullableScalar($record['statusInFamily'] ?? null) ?? '');
        if (str_contains($familyStatus, 'janda') || str_contains($familyStatus, 'duda')) {
            return CongregationMaritalStatus::Widowed;
        }

        return $this->truthy($record['married'] ?? null) ? CongregationMaritalStatus::Married : CongregationMaritalStatus::Single;
    }

    /** @param list<string> $warnings */
    private function calendarDate(mixed $value, string $field, array &$warnings): ?CarbonImmutable
    {
        $value = $this->nullableScalar($value);
        if ($value === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, config('app.timezone'))->startOfDay();
        } catch (Throwable) {
            $warnings[] = "{$field} tidak valid dan diubah menjadi null.";

            return null;
        }
    }

    private function truthy(mixed $value): bool
    {
        return in_array(Str::lower(trim((string) $value)), ['1', 'true', 'yes', 'ya', 'y'], true);
    }

    private function congregationNotes(array $record): ?string
    {
        $fields = [
            'username' => 'Username lama',
            'role' => 'Role Firebase',
            'bloodType' => 'Golongan darah',
            'lastEducation' => 'Pendidikan terakhir',
            'churchOrigin' => 'Asal gereja',
            'reasonToMovingChurch' => 'Alasan pindah gereja',
            'statusInFamily' => 'Status dalam keluarga',
            'headOfFamilyId' => 'Firebase UID kepala keluarga',
            'fatherFullName' => 'Nama ayah',
            'fatherFullname' => 'Nama ayah',
            'motherFullName' => 'Nama ibu',
            'husbandName' => 'Nama suami',
            'wifeName' => 'Nama istri',
            'childrenName' => 'Nama anak',
            'siblingsName' => 'Nama saudara',
            'holySpiritBaptism' => 'Baptisan Roh Kudus',
            'waterBaptisteryChurch' => 'Gereja baptisan air',
            'addressLat' => 'Latitude lama',
            'addressLong' => 'Longitude lama',
        ];
        $lines = [];
        foreach ($fields as $key => $label) {
            $value = $this->nullableScalar($record[$key] ?? null);
            if ($value !== null && ! ($value === '0' && in_array($key, ['addressLat', 'addressLong'], true))) {
                $lines[] = "{$label}: {$value}";
            }
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    private function prayerRequestCategory(mixed $value): PrayerRequestCategory
    {
        return match (Str::lower($this->nullableScalar($value) ?? '')) {
            'kunjungan' => PrayerRequestCategory::Ministry,
            default => PrayerRequestCategory::Other,
        };
    }

    private function prayerRequestStatus(mixed $value): PrayerRequestStatus
    {
        return match (Str::upper($this->nullableScalar($value) ?? '')) {
            'IN_PROGRESS', 'IN PROGRESS' => PrayerRequestStatus::InPrayer,
            'DONE', 'CLOSED' => PrayerRequestStatus::Closed,
            default => PrayerRequestStatus::New,
        };
    }

    private function prayerRequestNotes(array $record): ?string
    {
        $fields = [
            'prayType' => 'Jenis permohonan Firebase',
            'requesterId' => 'Firebase UID pemohon',
            'handlerId' => 'Firebase UID handler',
            'handlerName' => 'Nama handler lama',
            'prayResult' => 'Hasil/catatan penanganan lama',
        ];
        $lines = [];
        foreach ($fields as $key => $label) {
            $value = $this->nullableScalar($record[$key] ?? null);
            if ($value !== null) {
                $lines[] = "{$label}: {$value}";
            }
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    private function requiredString(array $record, string $key): string
    {
        $value = $this->nullableScalar($record[$key] ?? null);
        if ($value === null) {
            throw new InvalidArgumentException("Field {$key} wajib diisi.");
        }

        return $value;
    }

    private function nullableScalar(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @param list<string> $warnings */
    private function field(array $record, string $key, string $field, array &$warnings): mixed
    {
        if (! array_key_exists($key, $record)) {
            $warnings[] = "{$field} tidak ditemukan; nilai disimpan sebagai null.";

            return null;
        }

        return $record[$key];
    }

    /** @param list<string> $warnings */
    private function timestamp(mixed $value, string $field, array &$warnings): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            $warnings[] = "{$field} tidak ditemukan; status disimpan sebagai draft.";

            return null;
        }

        try {
            if (is_array($value)) {
                $seconds = $value['_seconds'] ?? $value['seconds'] ?? null;
                if (! is_numeric($seconds)) {
                    throw new InvalidArgumentException('format timestamp object tidak dikenal');
                }
                $milliseconds = ((int) $seconds * 1000) + intdiv((int) ($value['_nanoseconds'] ?? $value['nanoseconds'] ?? 0), 1_000_000);
            } elseif (is_numeric($value)) {
                $milliseconds = (int) $value;
                if (abs($milliseconds) < 100_000_000_000) {
                    $milliseconds *= 1000;
                }
            } else {
                return CarbonImmutable::parse((string) $value, config('app.timezone'));
            }

            return CarbonImmutable::createFromTimestampMs($milliseconds, config('app.timezone'));
        } catch (Throwable) {
            $warnings[] = "{$field} tidak valid dan diubah menjadi null.";

            return null;
        }
    }

    private function day(string $value): DayOfWeek
    {
        $key = Str::lower(str_replace(["'", '’'], '', trim($value)));

        return match ($key) {
            'senin', 'monday' => DayOfWeek::Monday,
            'selasa', 'tuesday' => DayOfWeek::Tuesday,
            'rabu', 'wednesday' => DayOfWeek::Wednesday,
            'kamis', 'thursday' => DayOfWeek::Thursday,
            'jumat', 'friday' => DayOfWeek::Friday,
            'sabtu', 'saturday' => DayOfWeek::Saturday,
            'minggu', 'ahad', 'sunday' => DayOfWeek::Sunday,
            default => throw new InvalidArgumentException("Hari '{$value}' tidak dikenali."),
        };
    }

    /** @param list<string> $warnings */
    private function time(mixed $value, string $field, array &$warnings): ?string
    {
        $value = $this->nullableScalar($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('/\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/', $value, $matches) !== 1) {
            $warnings[] = "{$field} tidak dapat dinormalisasi dan diubah menjadi null.";

            return null;
        }

        return sprintf('%02d:%02d:00', (int) $matches[1], (int) $matches[2]);
    }

    private function phone(mixed $value): ?string
    {
        $value = $this->nullableScalar($value);
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits === '' ? null : '+'.$digits;
    }

    /** @param list<string> $warnings */
    private function validUrl(mixed $value, string $field, array &$warnings): ?string
    {
        $value = $this->nullableScalar($value);
        if ($value === null) {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_URL) === false || ! in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $warnings[] = "{$field} bukan URL http/https yang valid dan diubah menjadi null.";

            return null;
        }

        return $value;
    }

    /** @param list<string> $warnings */
    private function firebaseProfileUrl(mixed $value, string $field, array &$warnings): ?string
    {
        $value = $this->nullableScalar($value);
        if ($value === null) {
            return null;
        }
        if (str_starts_with($value, '/v0/b/')) {
            return rtrim((string) config('firebase.storage.base_url'), '/').$value.'?alt=media';
        }

        return $this->validUrl($value, $field, $warnings);
    }

    /** @param list<string> $modules @return list<string> */
    private function normalizeModules(array $modules): array
    {
        $normalized = array_values(array_unique(array_map(fn (string $module): string => Str::of($module)->lower()->replace('_', '-')->toString(), $modules)));
        $invalid = array_diff($normalized, self::MODULES);
        if ($invalid !== []) {
            throw new InvalidArgumentException('Module tidak dikenal: '.implode(', ', $invalid));
        }

        return $normalized;
    }

    private function resolvePath(string $filename): string
    {
        $candidate = str_starts_with($filename, DIRECTORY_SEPARATOR) ? $filename : base_path($filename);
        $real = realpath($candidate);
        if ($real === false || ! is_file($real) || ! is_readable($real)) {
            throw new InvalidArgumentException("File Firebase tidak ditemukan atau tidak dapat dibaca: {$filename}");
        }

        return $real;
    }

    /** @return array{total:int,inserted:int,updated:int,skipped:int,failed:int} */
    private function emptyCounts(): array
    {
        return ['total' => 0, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
    }

    /** @param list<string> $issues */
    private function encodeIssues(array $issues): ?string
    {
        if ($issues === []) {
            return null;
        }

        $limited = array_slice($issues, 0, 100);
        if (count($issues) > 100) {
            $limited[] = sprintf('%d warning/error tambahan tidak ditampilkan.', count($issues) - 100);
        }

        return json_encode($limited, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'Import menghasilkan warning/error.';
    }
}

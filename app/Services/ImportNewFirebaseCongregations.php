<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Congregation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class ImportNewFirebaseCongregations
{
    public function __construct(private readonly FirebaseImportService $importer) {}

    /** @return array<string, mixed> */
    public function handle(string $file, bool $dryRun = false, ?int $expected = null): array
    {
        if (! is_file($file) || ! is_readable($file)) {
            throw new InvalidArgumentException('File ekspor Firebase tidak ditemukan atau tidak dapat dibaca.');
        }
        $source = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($source)) {
            throw new InvalidArgumentException('Ekspor harus berupa object users atau object dengan node users.');
        }
        $source = $source['users'] ?? $source;
        if (! is_array($source)) {
            throw new InvalidArgumentException('Node users tidak valid.');
        }

        return DB::transaction(function () use ($source, $dryRun, $expected): array {
            // Include archived identities: importing a new batch must never revive existing members.
            $query = Congregation::withTrashed()->select('id', 'legacy_firebase_uid', 'member_number');
            if (! $dryRun) {
                $query->lockForUpdate();
            }
            $existing = $query->get();
            $knownUids = $existing->whereNotNull('legacy_firebase_uid')->keyBy('legacy_firebase_uid');
            $knownNumbers = $existing->mapWithKeys(fn ($item): array => [strtoupper($item->member_number) => true])->all();
            $report = ['total' => count($source), 'existing' => 0, 'deleted_in_export' => 0, 'duplicate_new' => 0, 'selected' => [], 'duplicates' => [], 'warnings' => [], 'inserted' => 0, 'import_id' => null];
            $new = [];
            $seen = [];

            foreach ($source as $key => $record) {
                if (! is_array($record) || (isset($record['id']) && ! is_scalar($record['id']))) {
                    throw new InvalidArgumentException('Ekspor berisi record atau Firebase UID yang tidak valid.');
                }
                $uid = trim((string) ($record['id'] ?? '')) ?: (string) $key;
                if (isset($seen[$uid])) {
                    throw new InvalidArgumentException('Ekspor mengandung Firebase UID berulang.');
                }
                $seen[$uid] = true;
                if ($knownUids->has($uid)) {
                    $report['existing']++;

                    continue;
                }
                if (isset($record['deletedAt']) && trim((string) $record['deletedAt']) !== '') {
                    $report['deleted_in_export']++;

                    continue;
                }
                if (! is_string($record['fullName'] ?? null) || trim($record['fullName']) === '') {
                    throw new InvalidArgumentException('Record jemaat baru wajib memiliki fullName.');
                }
                $this->nijNumber($record['nij'] ?? null);
                $record['id'] = $uid;
                // Never copy authentication credentials into the temporary import batch.
                unset($record['password'], $record['fcmToken']);
                $new[$uid] = $record;
            }

            // Only new identities participate in deduplication. Existing database rows are read-only.
            foreach (collect($new)->groupBy(fn (array $row): string => mb_strtolower(preg_replace('/\s+/u', ' ', trim($row['fullName'])))) as $group) {
                $group = $group->sortByDesc(fn (array $row): int => $this->nijNumber($row['nij']))->values();
                $winner = $group->first();
                foreach ($group->skip(1) as $loser) {
                    if ($this->nijNumber($winner['nij']) === $this->nijNumber($loser['nij'])) {
                        throw new InvalidArgumentException('Nama duplikat di batch baru memiliki NIJ yang sama: '.$winner['nij']);
                    }
                    unset($new[$loser['id']]);
                    $report['duplicates'][] = ['name' => $winner['fullName'], 'keep' => $winner['nij'], 'skip' => $loser['nij']];
                    $report['duplicate_new']++;
                }
            }

            $batchNumbers = [];
            foreach ($new as $record) {
                $number = strtoupper(trim($record['nij']));
                if (isset($knownNumbers[$number]) || isset($batchNumbers[$number])) {
                    throw new InvalidArgumentException("NIJ {$number} sudah dipakai oleh identitas lain. Tidak ada data yang diubah.");
                }
                $batchNumbers[$number] = true;
                $report['selected'][] = ['name' => $record['fullName'], 'nij' => $number];
            }
            if ($expected !== null && count($new) !== $expected) {
                throw new InvalidArgumentException('Jumlah record terpilih '.count($new).", tidak sesuai --expected={$expected}. Jalankan --dry-run tanpa --expected untuk memeriksa kondisi production.");
            }
            if ($new === []) {
                return $report;
            }

            $temporary = tempnam(sys_get_temp_dir(), 'firebase-new-congregations-');
            if ($temporary === false) {
                throw new RuntimeException('Tidak dapat membuat batch impor sementara.');
            }
            try {
                $json = json_encode(['users' => $new], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                if (file_put_contents($temporary, $json) === false) {
                    throw new RuntimeException('Tidak dapat menulis batch impor sementara.');
                }
                $import = $this->importer->import($temporary, ['only' => ['congregations'], 'dry_run' => $dryRun, 'force' => false]);
                if ($import['totals']['failed'] > 0 || $import['totals']['inserted'] !== count($new) || $import['totals']['updated'] !== 0) {
                    throw new RuntimeException('Impor dibatalkan seluruhnya: '.implode('; ', array_slice($import['warnings'], 0, 10)));
                }
                $report['inserted'] = $import['totals']['inserted'];
                $report['warnings'] = $import['warnings'];
                $report['import_id'] = $import['import_id'];

                return $report;
            } finally {
                unlink($temporary);
            }
        });
    }

    private function nijNumber(mixed $nij): int
    {
        if (! is_string($nij) || ! preg_match('/^HC-(\d+)$/i', trim($nij), $matches)) {
            throw new InvalidArgumentException('NIJ jemaat baru harus berformat HC-angka, misalnya HC-00786.');
        }

        return (int) $matches[1];
    }
}

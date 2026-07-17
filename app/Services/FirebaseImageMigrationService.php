<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DataImportStatus;
use App\Models\Announcement;
use App\Models\DataImport;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class FirebaseImageMigrationService
{
    /**
     * @param  array{dry_run?:bool,limit?:int,chunk?:int,timeout?:int,retries?:int}  $options
     * @param  null|callable(int,int):void  $progress
     * @return array{total:int,migrated:int,skipped:int,failed:int,warnings:list<string>,import_id:?int,dry_run:bool}
     */
    public function migrate(array $options = [], ?callable $progress = null): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $limit = max(0, (int) ($options['limit'] ?? 0));
        $chunk = max(1, min(500, (int) ($options['chunk'] ?? 50)));
        $timeout = max(1, min(60, (int) ($options['timeout'] ?? config('firebase.images.timeout', 10))));
        $retries = max(1, min(5, (int) ($options['retries'] ?? config('firebase.images.retries', 3))));

        $query = Announcement::query()->whereNull('image')->whereNotNull('legacy_image_url')->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }
        $records = $query->get(['id', 'legacy_image_url']);
        $report = ['total' => $records->count(), 'migrated' => 0, 'skipped' => 0, 'failed' => 0, 'warnings' => [], 'import_id' => null, 'dry_run' => $dryRun];
        $progress?->__invoke(0, $report['total']);

        $log = $dryRun ? null : DataImport::query()->create([
            'type' => 'firebase_images',
            'filename' => 'remote-announcement-images',
            'checksum' => hash('sha256', $records->pluck('legacy_image_url')->implode('|')),
            'status' => DataImportStatus::Processing,
            'total_records' => $report['total'],
            'started_at' => now(),
        ]);
        $report['import_id'] = $log?->id;
        $processed = 0;

        foreach ($records->chunk($chunk) as $batch) {
            foreach ($batch as $announcement) {
                try {
                    $url = (string) $announcement->legacy_image_url;
                    $this->assertSafeUrl($url);
                    if ($dryRun) {
                        $report['migrated']++;
                    } else {
                        $contents = $this->download($url, $timeout, $retries);
                        [$extension] = $this->validateImage($contents);
                        $directory = trim((string) config('firebase.images.directory', 'announcements/imported'), '/');
                        $path = $directory.'/'.Str::uuid().'.'.$extension;
                        $disk = (string) config('firebase.images.disk', 'public');
                        if (! Storage::disk($disk)->put($path, $contents)) {
                            throw new RuntimeException('File gagal disimpan ke Laravel storage.');
                        }
                        $announcement->updateQuietly(['image' => $path]);
                        $report['migrated']++;
                    }
                } catch (Throwable $exception) {
                    $report['failed']++;
                    $report['warnings'][] = "Announcement #{$announcement->id}: {$exception->getMessage()}";
                }

                $processed++;
                $progress?->__invoke($processed, $report['total']);
            }
        }

        if ($log !== null) {
            $log->update([
                'status' => $report['failed'] > 0 ? DataImportStatus::CompletedWithErrors : DataImportStatus::Completed,
                'inserted_records' => $report['migrated'],
                'skipped_records' => $report['skipped'],
                'failed_records' => $report['failed'],
                'finished_at' => now(),
                'error_summary' => $report['warnings'] === [] ? null : json_encode(array_slice($report['warnings'], 0, 100), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
        }

        return $report;
    }

    private function download(string $url, int $timeout, int $retries): string
    {
        $response = $this->client($timeout, $retries)->get($url);
        if (! $response->successful()) {
            throw new RuntimeException("Download gagal dengan HTTP {$response->status()}.");
        }
        $contents = $response->body();
        if ($contents === '') {
            throw new RuntimeException('Response image kosong.');
        }

        return $contents;
    }

    private function client(int $timeout, int $retries): PendingRequest
    {
        return Http::timeout($timeout)->connectTimeout(min(5, $timeout))->retry($retries, 250, throw: false);
    }

    /** @return array{string,string} */
    private function validateImage(string $contents): array
    {
        $maxBytes = (int) config('firebase.images.max_bytes', 5 * 1024 * 1024);
        if (strlen($contents) > $maxBytes) {
            throw new RuntimeException('Ukuran image melewati batas '.number_format($maxBytes).' bytes.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (! is_string($mime) || ! in_array($mime, (array) config('firebase.images.allowed_mime_types', array_keys($map)), true) || ! isset($map[$mime])) {
            throw new RuntimeException('MIME image tidak diizinkan: '.($mime ?: 'unknown').'.');
        }

        return [$map[$mime], $mime];
    }

    private function assertSafeUrl(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('Legacy image URL tidak valid.');
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new RuntimeException('Legacy image URL mengarah ke host lokal.');
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new RuntimeException('Legacy image URL mengarah ke IP privat/reserved.');
        }
    }
}

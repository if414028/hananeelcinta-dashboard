<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FirebaseImageMigrationService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

final class FirebaseMigrateImagesCommand extends Command
{
    protected $signature = 'firebase:migrate-images
        {--dry-run : Tampilkan kandidat tanpa download atau menulis database}
        {--limit=0 : Batasi jumlah announcement; 0 berarti semua}
        {--chunk=50 : Jumlah record per batch}
        {--timeout=10 : Timeout request dalam detik}
        {--retries=3 : Jumlah percobaan download}';

    protected $description = 'Pindahkan legacy Firebase announcement images ke Laravel public storage';

    public function handle(FirebaseImageMigrationService $service): int
    {
        try {
            $bar = null;
            $report = $service->migrate([
                'dry_run' => (bool) $this->option('dry-run'),
                'limit' => (int) $this->option('limit'),
                'chunk' => (int) $this->option('chunk'),
                'timeout' => (int) $this->option('timeout'),
                'retries' => (int) $this->option('retries'),
            ], function (int $processed, int $total) use (&$bar): void {
                if ($processed === 0) {
                    $bar = $this->output->createProgressBar($total);
                    $bar->start();
                    if ($total === 0) {
                        $bar->finish();
                        $this->newLine();
                    }

                    return;
                }
                /** @var ProgressBar $bar */
                $bar->setProgress($processed);
                if ($processed === $total) {
                    $bar->finish();
                    $this->newLine();
                }
            });

            $this->components->info($report['dry_run'] ? 'Firebase image migration dry-run completed.' : 'Firebase image migration completed.');
            $this->table(['Total', 'Migrated', 'Skipped', 'Failed'], [[$report['total'], $report['migrated'], $report['skipped'], $report['failed']]]);
            foreach (array_slice($report['warnings'], 0, 10) as $warning) {
                $this->line(' - '.$warning);
            }

            return $report['failed'] > 0 ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Firebase image migration gagal: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}

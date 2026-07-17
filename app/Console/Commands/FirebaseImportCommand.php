<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FirebaseImportService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

final class FirebaseImportCommand extends Command
{
    protected $signature = 'firebase:import
        {file : Path ke Firebase Realtime Database JSON export}
        {--dry-run : Validasi dan simulasi tanpa menulis database atau import log}
        {--only= : Module: announcements, family-altars, pastor-messages (dapat dipisahkan koma)}
        {--force : Update record yang sudah memiliki legacy key/index}
        {--chunk=100 : Jumlah record per database transaction}';

    protected $description = 'Import Firebase Realtime Database JSON ke MySQL secara idempotent';

    public function handle(FirebaseImportService $service): int
    {
        try {
            $only = $this->option('only');
            $modules = is_string($only) && trim($only) !== '' ? array_values(array_filter(array_map('trim', explode(',', $only)))) : FirebaseImportService::MODULES;
            $bars = [];
            $report = $service->import((string) $this->argument('file'), [
                'dry_run' => (bool) $this->option('dry-run'),
                'force' => (bool) $this->option('force'),
                'only' => $modules,
                'chunk' => (int) $this->option('chunk'),
            ], function (string $module, int $processed, int $total) use (&$bars): void {
                if ($processed === 0) {
                    $this->newLine();
                    $this->components->info('Importing '.str_replace('-', ' ', $module));
                    $bars[$module] = $this->output->createProgressBar($total);
                    $bars[$module]->start();
                    if ($total === 0) {
                        $bars[$module]->finish();
                        $this->newLine();
                    }

                    return;
                }
                /** @var ProgressBar $bar */
                $bar = $bars[$module];
                $bar->setProgress($processed);
                if ($processed === $total) {
                    $bar->finish();
                    $this->newLine();
                }
            });

            $this->newLine();
            $this->components->info($report['dry_run'] ? 'Firebase dry-run completed.' : 'Firebase import completed.');
            $rows = [];
            foreach ($report['modules'] as $module => $counts) {
                $rows[] = [str_replace('-', ' ', (string) $module), $counts['total'], $counts['inserted'], $counts['updated'], $counts['skipped'], $counts['failed']];
            }
            $this->table(['Module', 'Total', 'Inserted', 'Updated', 'Skipped', 'Failed'], $rows);
            if ($report['warnings'] !== []) {
                $this->components->warn(count($report['warnings']).' warning/error dicatat. Contoh:');
                foreach (array_slice($report['warnings'], 0, 10) as $warning) {
                    $this->line(' - '.$warning);
                }
            }
            if ($report['import_id'] !== null) {
                $this->line('Import log ID: '.$report['import_id']);
            }

            return $report['totals']['failed'] > 0 ? self::FAILURE : self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Firebase import gagal: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}

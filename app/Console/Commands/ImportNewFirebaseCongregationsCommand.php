<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ImportNewFirebaseCongregations;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

final class ImportNewFirebaseCongregationsCommand extends Command
{
    protected $signature = 'firebase:import-new-congregations
        {file : Path ekspor users Firebase, baik langsung maupun dengan node users}
        {--dry-run : Simulasi tanpa mengubah database atau membuat log impor}
        {--expected= : Batalkan jika jumlah record baru terpilih tidak sesuai angka ini}';

    protected $description = 'Tambahkan jemaat baru; nama duplikat hanya di batch baru dipilih berdasarkan NIJ tertinggi, data lama tidak diubah';

    public function handle(ImportNewFirebaseCongregations $service): int
    {
        try {
            $expected = $this->option('expected');
            if ($expected !== null && (! ctype_digit((string) $expected))) {
                throw new InvalidArgumentException('--expected harus berupa bilangan bulat non-negatif.');
            }
            $dryRun = (bool) $this->option('dry-run');
            $report = $service->handle((string) $this->argument('file'), $dryRun, $expected === null ? null : (int) $expected);

            $this->table(['Ekspor', 'Sudah ada (dilewati)', 'Dihapus di ekspor', 'Duplikat batch baru (dilewati)', $dryRun ? 'Akan ditambahkan' : 'Ditambahkan'], [[
                $report['total'], $report['existing'], $report['deleted_in_export'], $report['duplicate_new'], $report['inserted'],
            ]]);
            if ($report['selected'] !== []) {
                $this->table(['Nama jemaat baru', 'NIJ'], array_map(fn (array $row): array => [$row['name'], $row['nij']], $report['selected']));
            }
            if ($report['duplicates'] !== []) {
                $this->table(['Nama duplikat di batch baru', 'NIJ dipakai', 'NIJ dilewati'], array_map(fn (array $row): array => array_values($row), $report['duplicates']));
            }
            foreach (array_slice($report['warnings'], 0, 10) as $warning) {
                $this->warn($warning);
            }
            if ($report['import_id'] !== null) {
                $this->line('Import log ID: '.$report['import_id']);
            }
            $this->info($dryRun ? 'Simulasi selesai. Tidak ada perubahan database.' : 'Impor selesai. Record jemaat yang sudah ada tidak diubah atau dihapus.');

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}

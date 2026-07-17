<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Congregation;
use App\Models\MobileAccount;
use Illuminate\Console\Command;

final class SyncMobileAccountsCommand extends Command
{
    protected $signature = 'mobile-accounts:sync {--dry-run : Validate and count without writing data}';

    protected $description = 'Synchronize Firebase-linked congregations into mobile accounts';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = ['total' => 0, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $total = Congregation::query()->whereNotNull('legacy_firebase_uid')->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Congregation::query()
            ->whereNotNull('legacy_firebase_uid')
            ->orderBy('id')
            ->chunkById(200, function ($congregations) use (&$counts, $dryRun, $bar): void {
                foreach ($congregations as $congregation) {
                    $counts['total']++;
                    $accountByUid = MobileAccount::withTrashed()->where('firebase_uid', $congregation->legacy_firebase_uid)->first();
                    $accountByCongregation = MobileAccount::withTrashed()->where('congregation_id', $congregation->id)->first();
                    $account = $accountByUid ?? $accountByCongregation;

                    if (($accountByUid !== null && $accountByUid->congregation_id !== $congregation->id)
                        || ($accountByCongregation !== null && $accountByCongregation->firebase_uid !== $congregation->legacy_firebase_uid)) {
                        $counts['failed']++;
                        $this->newLine();
                        $this->warn("UID {$congregation->legacy_firebase_uid} sudah terhubung ke jemaat lain.");
                        $bar->advance();

                        continue;
                    }

                    $data = [
                        'congregation_id' => $congregation->id,
                        'firebase_uid' => $congregation->legacy_firebase_uid,
                        'email' => $congregation->email,
                        'is_active' => $congregation->is_active,
                    ];

                    if ($account === null) {
                        $counts['inserted']++;
                        if (! $dryRun) {
                            MobileAccount::query()->create($data);
                        }
                    } else {
                        $account->fill($data);
                        if ($account->trashed() || $account->isDirty()) {
                            $counts['updated']++;
                            if (! $dryRun) {
                                if ($account->trashed()) {
                                    $account->restore();
                                }
                                $account->save();
                            }
                        } else {
                            $counts['skipped']++;
                        }
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->table(['Total', 'Inserted', 'Updated', 'Skipped', 'Failed'], [[...array_values($counts)]]);
        $this->info($dryRun ? 'Mobile account dry-run completed.' : 'Mobile accounts synchronized.');

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;

final class GeneratePrayerReference
{
    public function handle(): string
    {
        $date = now()->toDateString();

        return DB::transaction(function () use ($date): string {
            DB::table('prayer_reference_sequences')->insertOrIgnore(['date' => $date, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $sequence = DB::table('prayer_reference_sequences')->where('date', $date)->lockForUpdate()->first();
            $next = ((int) $sequence->last_number) + 1;
            DB::table('prayer_reference_sequences')->where('date', $date)->update(['last_number' => $next, 'updated_at' => now()]);

            return sprintf('PR-%s-%04d', now()->format('Ymd'), $next);
        }, 3);
    }
}

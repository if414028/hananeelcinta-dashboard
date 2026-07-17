<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;

final class GenerateMemberNumber
{
    public function handle(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($year): string {
            DB::table('member_number_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $sequence = DB::table('member_number_sequences')->where('year', $year)->lockForUpdate()->first();
            $next = ((int) $sequence->last_number) + 1;
            DB::table('member_number_sequences')->where('year', $year)->update(['last_number' => $next, 'updated_at' => now()]);

            return sprintf('HC-%d-%05d', $year, $next);
        }, 3);
    }
}

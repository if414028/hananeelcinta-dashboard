<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CongregationMembershipStatus: string
{
    use HasOptions;
    case Visitor = 'visitor';
    case Regular = 'regular';
    case Member = 'member';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Visitor => 'Pengunjung', self::Regular => 'Jemaat Tetap', self::Member => 'Anggota', self::Inactive => 'Tidak Aktif'
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Member => 'success', self::Inactive => 'warning', default => 'neutral'
        };
    }
}

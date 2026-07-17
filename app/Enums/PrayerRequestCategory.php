<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PrayerRequestCategory: string
{
    use HasOptions;
    case Healing = 'healing';
    case Family = 'family';
    case Finance = 'finance';
    case Career = 'career';
    case Ministry = 'ministry';
    case Relationship = 'relationship';
    case Salvation = 'salvation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Healing => 'Kesembuhan', self::Family => 'Keluarga', self::Finance => 'Keuangan', self::Career => 'Karier', self::Ministry => 'Pelayanan', self::Relationship => 'Relasi', self::Salvation => 'Keselamatan', self::Other => 'Lainnya'
        };
    }

    public function badgeClass(): string
    {
        return 'neutral';
    }
}

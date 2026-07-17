<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CongregationMaritalStatus: string
{
    use HasOptions;
    case Single = 'single';
    case Married = 'married';
    case Widowed = 'widowed';
    case Divorced = 'divorced';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Belum Menikah', self::Married => 'Menikah', self::Widowed => 'Duda/Janda', self::Divorced => 'Bercerai'
        };
    }

    public function badgeClass(): string
    {
        return 'neutral';
    }
}

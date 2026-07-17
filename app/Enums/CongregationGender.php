<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CongregationGender: string
{
    use HasOptions;
    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return $this === self::Male ? 'Laki-laki' : 'Perempuan';
    }

    public function badgeClass(): string
    {
        return 'neutral';
    }
}

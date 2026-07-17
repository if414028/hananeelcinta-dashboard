<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BaptismStatus: string
{
    use HasOptions;
    case NotBaptized = 'not_baptized';
    case Baptized = 'baptized';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::NotBaptized => 'Belum Dibaptis', self::Baptized => 'Sudah Dibaptis', self::Unknown => 'Tidak Diketahui'
        };
    }

    public function badgeClass(): string
    {
        return $this === self::Baptized ? 'success' : 'neutral';
    }
}

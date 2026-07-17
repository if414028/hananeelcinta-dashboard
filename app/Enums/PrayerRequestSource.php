<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PrayerRequestSource: string
{
    use HasOptions;
    case Website = 'website';
    case Android = 'android';
    case Ios = 'ios';
    case Admin = 'admin';
    case Migration = 'migration';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website', self::Android => 'Android', self::Ios => 'iOS', self::Admin => 'Admin', self::Migration => 'Migrasi'
        };
    }

    public function badgeClass(): string
    {
        return 'neutral';
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PrayerRequestStatus: string
{
    use HasOptions;
    case Open = 'open';
    case InPrayer = 'in_prayer';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open', self::InPrayer => 'Sedang Didoakan', self::Closed => 'Selesai'
        };
    }

    /** @return list<self> */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Open => [self::InPrayer],
            self::InPrayer => [self::Closed],
            self::Closed => [],
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'warning', self::Closed => 'success', default => 'neutral'
        };
    }
}

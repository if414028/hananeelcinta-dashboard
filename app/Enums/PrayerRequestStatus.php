<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PrayerRequestStatus: string
{
    use HasOptions;
    case New = 'new';
    case InPrayer = 'in_prayer';
    case FollowUp = 'follow_up';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru', self::InPrayer => 'Sedang Didoakan', self::FollowUp => 'Tindak Lanjut', self::Answered => 'Terjawab', self::Closed => 'Ditutup'
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'warning', self::Answered => 'success', default => 'neutral'
        };
    }
}

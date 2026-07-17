<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AnnouncementStatus: string
{
    use HasOptions;
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf', self::Published => 'Dipublikasikan', self::Archived => 'Diarsipkan'
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Published => 'success', self::Archived => 'warning', self::Draft => 'neutral'
        };
    }
}

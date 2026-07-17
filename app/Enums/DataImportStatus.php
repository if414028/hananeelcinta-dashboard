<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DataImportStatus: string
{
    use HasOptions;
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu', self::Processing => 'Diproses', self::Completed => 'Selesai', self::CompletedWithErrors => 'Selesai dengan Kesalahan', self::Failed => 'Gagal'
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed => 'success', self::Failed, self::CompletedWithErrors => 'warning', default => 'neutral'
        };
    }
}

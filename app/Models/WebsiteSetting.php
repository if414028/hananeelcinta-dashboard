<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\WebsiteSettings;
use Database\Factories\WebsiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class WebsiteSetting extends Model
{
    /** @use HasFactory<WebsiteSettingFactory> */
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    protected static function booted(): void
    {
        self::saved(fn () => app(WebsiteSettings::class)->forget());
        self::deleted(fn () => app(WebsiteSettings::class)->forget());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['group', 'key', 'type', 'is_public'])->logOnlyDirty();
    }
}

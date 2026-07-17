<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestSource;
use App\Enums\PrayerRequestStatus;
use Database\Factories\PrayerRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class PrayerRequest extends Model
{
    /** @use HasFactory<PrayerRequestFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['admin_notes', 'handled_by', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['prayer_category' => PrayerRequestCategory::class, 'status' => PrayerRequestStatus::class, 'source' => PrayerRequestSource::class, 'is_anonymous' => 'boolean', 'is_confidential' => 'boolean', 'handled_at' => 'datetime'];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['reference_number', 'prayer_category', 'status', 'handled_by', 'handled_at'])->logOnlyDirty();
    }
}

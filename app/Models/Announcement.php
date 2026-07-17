<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnnouncementStatus;
use App\Models\Concerns\HasAuditUsers;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasAuditUsers, HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => AnnouncementStatus::class, 'published_at' => 'datetime', 'expired_at' => 'datetime', 'is_featured' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AnnouncementStatus::Published)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn (Builder $query): Builder => $query->whereNull('expired_at')->orWhere('expired_at', '>', now()));
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->image
            ? Storage::disk('public')->url($this->image)
            : ($this->legacy_image_url ?: asset('images/placeholder-content.svg')));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['title', 'slug', 'status', 'published_at', 'expired_at', 'is_featured'])->logOnlyDirty();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PastorMessageStatus;
use App\Models\Concerns\HasAuditUsers;
use Database\Factories\PastorMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class PastorMessage extends Model
{
    /** @use HasFactory<PastorMessageFactory> */
    use HasAuditUsers, HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => PastorMessageStatus::class, 'published_at' => 'datetime', 'is_featured' => 'boolean', 'view_count' => 'integer'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PastorMessageStatus::Published)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['title', 'slug', 'writer', 'status', 'published_at', 'is_featured'])->logOnlyDirty();
    }
}

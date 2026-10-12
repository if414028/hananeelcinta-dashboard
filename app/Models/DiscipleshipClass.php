<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class DiscipleshipClass extends Model
{
    use HasAuditUsers, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Congregation::class, 'discipleship_enrollments')
            ->withPivot('completed_at')->withTimestamps();
    }

    public function bannerUrl(): string
    {
        return Storage::disk('public')->url($this->banner);
    }

    public function badgeUrl(): string
    {
        return Storage::disk('public')->url($this->badge);
    }

    public function statusLabel(): string
    {
        if ($this->completed_at) {
            return 'Selesai';
        }
        if ($this->starts_at->isFuture()) {
            return 'Akan datang';
        }

        return $this->ends_at->isPast() ? 'Menunggu penyelesaian' : 'Berlangsung';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'description', 'teacher_name', 'banner', 'badge', 'starts_at', 'ends_at', 'completed_at', 'completed_by'])->logOnlyDirty();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Models\Concerns\HasAuditUsers;
use Database\Factories\FamilyAltarFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class FamilyAltar extends Model
{
    /** @use HasFactory<FamilyAltarFactory> */
    use HasAuditUsers, HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['day_of_week' => DayOfWeek::class, 'is_active' => 'boolean', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function whatsappUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->contact_phone) {
                return null;
            }
            $number = preg_replace('/\D+/', '', $this->contact_phone);
            if (str_starts_with($number, '0')) {
                $number = '62'.substr($number, 1);
            }

            return 'https://wa.me/'.$number;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'day_of_week', 'start_time', 'is_active', 'sort_order'])->logOnlyDirty();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BaptismStatus;
use App\Enums\CongregationGender;
use App\Enums\CongregationMaritalStatus;
use App\Enums\CongregationMembershipStatus;
use App\Models\Concerns\HasAuditUsers;
use Database\Factories\CongregationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Congregation extends Model
{
    /** @use HasFactory<CongregationFactory> */
    use HasAuditUsers, HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'gender' => CongregationGender::class,
            'marital_status' => CongregationMaritalStatus::class,
            'baptism_status' => BaptismStatus::class,
            'membership_status' => CongregationMembershipStatus::class,
            'date_of_birth' => 'date', 'baptism_date' => 'date', 'joined_at' => 'date', 'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['member_number', 'full_name', 'membership_status', 'is_active'])->logOnlyDirty();
    }
}

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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
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

    public function mobileAccount(): HasOne
    {
        return $this->hasOne(MobileAccount::class);
    }

    public function profilePhotoUrl(): ?string
    {
        if ($this->profile_photo) {
            return Storage::disk('public')->url($this->profile_photo);
        }

        if ($this->legacy_profile_photo_url) {
            if (str_starts_with($this->legacy_profile_photo_url, '/')) {
                return rtrim((string) config('firebase.storage.base_url'), '/').$this->legacy_profile_photo_url.'?alt=media';
            }

            return $this->legacy_profile_photo_url;
        }

        if (! $this->legacy_firebase_uid) {
            return null;
        }

        $baseUrl = rtrim((string) config('firebase.storage.base_url'), '/');
        $bucket = (string) config('firebase.storage.bucket');
        $object = rawurlencode($this->legacy_firebase_uid.'/'.config('firebase.storage.profile_object'));

        return "{$baseUrl}/v0/b/{$bucket}/o/{$object}?alt=media";
    }
}

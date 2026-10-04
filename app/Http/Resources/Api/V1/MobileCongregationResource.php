<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MobileCongregationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_number' => $this->member_number,
            'full_name' => $this->full_name,
            'role' => $this->resource->mobileRole(),
            'nickname' => $this->nickname,
            'gender' => $this->gender->value,
            'place_of_birth' => $this->place_of_birth,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'marital_status' => $this->marital_status?->value,
            'phone_number' => $this->phone_number,
            'whatsapp_number' => $this->whatsapp_number,
            'email' => $this->email,
            'address' => [
                'street' => $this->address,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
            ],
            'occupation' => $this->occupation,
            'blood_type' => $this->blood_type,
            'last_education' => $this->last_education,
            'baptism_status' => $this->baptism_status->value,
            'baptism_date' => $this->baptism_date?->format('Y-m-d'),
            'baptism_church' => $this->baptism_church,
            'holy_spirit_baptism' => $this->holy_spirit_baptism,
            'church_origin' => $this->church_origin,
            'reason_to_move_church' => $this->reason_to_move_church,
            'family_status' => $this->family_status,
            'wife_name' => $this->wife_name,
            'husband_name' => $this->husband_name,
            'children_names' => $this->children_names ?? [],
            'siblings_names' => $this->siblings_names ?? [],
            'membership_status' => $this->membership_status->value,
            'joined_at' => $this->joined_at?->format('Y-m-d'),
            'profile_photo_url' => $this->profile_photo || $this->legacy_profile_photo_url ? $this->profilePhotoUrl() : null,
            'is_active' => $this->is_active,
        ];
    }
}

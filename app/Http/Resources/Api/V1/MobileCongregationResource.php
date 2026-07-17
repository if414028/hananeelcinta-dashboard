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
            'baptism_status' => $this->baptism_status->value,
            'baptism_date' => $this->baptism_date?->format('Y-m-d'),
            'membership_status' => $this->membership_status->value,
            'joined_at' => $this->joined_at?->format('Y-m-d'),
            'profile_photo_url' => $this->profilePhotoUrl(),
            'is_active' => $this->is_active,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MobileAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'account' => [
                'id' => $this->id,
                'uid' => $this->firebase_uid,
                'email' => $this->email,
                'email_verified' => $this->email_verified_at !== null,
                'providers' => $this->provider_ids ?? [],
                'authenticated_at' => $this->last_authenticated_at?->toAtomString(),
            ],
            'profile' => (new MobileCongregationResource($this->congregation))->resolve($request),
        ];
    }
}

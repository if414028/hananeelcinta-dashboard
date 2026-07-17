<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'excerpt' => $this->excerpt, 'description' => $this->description, 'image_url' => $this->image_url, 'contact_person' => ['name' => $this->contact_person_name, 'phone' => $this->contact_person_phone], 'information_url' => $this->information_url, 'published_at' => $this->published_at?->toAtomString(), 'expired_at' => $this->expired_at?->toAtomString(), 'is_featured' => $this->is_featured];
    }
}

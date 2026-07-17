<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class PastorMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'writer' => $this->writer, 'content' => $this->content, 'excerpt' => $this->excerpt, 'featured_image_url' => $this->featured_image ? Storage::disk('public')->url($this->featured_image) : null, 'published_at' => $this->published_at?->toAtomString(), 'is_featured' => $this->is_featured, 'view_count' => $this->view_count];
    }
}

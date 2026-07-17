<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class FamilyAltarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description, 'day' => ['value' => $this->day_of_week->value, 'label' => $this->day_of_week->label()], 'start_time' => $this->start_time, 'end_time' => $this->end_time, 'location_name' => $this->location_name, 'address' => $this->address, 'city' => $this->city, 'pic_name' => $this->pic_name, 'contact_phone' => $this->contact_phone, 'whatsapp_url' => $this->whatsapp_url, 'coordinates' => ['latitude' => $this->latitude, 'longitude' => $this->longitude], 'map_url' => $this->map_url, 'image_url' => $this->image ? Storage::disk('public')->url($this->image) : null];
    }
}

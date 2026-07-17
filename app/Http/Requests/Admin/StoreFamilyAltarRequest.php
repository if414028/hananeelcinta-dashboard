<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFamilyAltarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('family_altars.create') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'day_of_week' => ['required', Rule::enum(DayOfWeek::class)], 'start_time' => ['nullable', 'date_format:H:i'], 'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'], 'location_name' => ['nullable', 'string', 'max:255'], 'address' => ['nullable', 'string'], 'city' => ['nullable', 'string', 'max:100'], 'pic_name' => ['nullable', 'string', 'max:255'], 'contact_phone' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'], 'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180'], 'map_url' => ['nullable', 'url', 'max:2000'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0']];
    }
}

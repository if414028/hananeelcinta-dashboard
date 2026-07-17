<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AnnouncementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('announcements.create') ?? false;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'excerpt' => ['nullable', 'string', 'max:1000'], 'description' => ['required', 'string'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'contact_person_name' => ['nullable', 'string', 'max:255'], 'contact_person_phone' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'], 'information_url' => ['nullable', 'url', 'max:2000'], 'published_at' => ['nullable', 'date'], 'expired_at' => ['nullable', 'date', 'after:published_at'], 'status' => ['required', Rule::enum(AnnouncementStatus::class)], 'is_featured' => ['sometimes', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0']];
    }
}

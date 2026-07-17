<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PastorMessageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePastorMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pastor_messages.create') ?? false;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'writer' => ['required', 'string', 'max:255'], 'content' => ['required', 'string'], 'excerpt' => ['nullable', 'string', 'max:1000'], 'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'published_at' => ['nullable', 'date'], 'status' => ['required', Rule::enum(PastorMessageStatus::class)], 'is_featured' => ['sometimes', 'boolean']];
    }
}

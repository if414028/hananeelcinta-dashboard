<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class AnnouncementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'], 'search' => ['nullable', 'string', 'max:100'], 'featured' => ['nullable', 'boolean'], 'published_after' => ['nullable', 'date'], 'published_before' => ['nullable', 'date', 'after_or_equal:published_after']];
    }
}

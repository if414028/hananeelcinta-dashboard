<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FamilyAltarIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'], 'day' => ['nullable', Rule::enum(DayOfWeek::class)], 'city' => ['nullable', 'string', 'max:100'], 'search' => ['nullable', 'string', 'max:100']];
    }
}

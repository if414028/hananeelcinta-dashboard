<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PrayerRequestCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePrayerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['client_platform' => mb_strtolower((string) $this->header('X-App-Platform'))]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'], 'prayer_category' => ['required', Rule::enum(PrayerRequestCategory::class)], 'prayer_content' => ['required', 'string', 'min:10', 'max:5000'], 'is_anonymous' => ['sometimes', 'boolean'], 'is_confidential' => ['sometimes', 'boolean'], 'privacy_accepted' => ['accepted'], 'client_platform' => ['nullable', Rule::in(['android', 'ios'])]];
    }
}

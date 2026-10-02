<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePrayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('prayer_requests.update') ?? false;
    }

    public function rules(): array
    {
        return ['prayer_result' => ['nullable', 'string', 'max:5000']];
    }
}

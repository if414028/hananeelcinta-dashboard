<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PrayerRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePrayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('prayer_requests.update') ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(PrayerRequestStatus::class)], 'handled_by' => ['nullable', 'exists:users,id'], 'admin_notes' => ['nullable', 'string', 'max:5000']];
    }
}

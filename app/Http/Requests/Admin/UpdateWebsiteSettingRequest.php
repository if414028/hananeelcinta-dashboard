<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateWebsiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    public function rules(): array
    {
        return ['settings' => ['required', 'array'], 'settings.*' => ['nullable', 'string', 'max:20000']];
    }
}

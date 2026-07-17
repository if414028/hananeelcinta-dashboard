<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateAdminUserRequest extends StoreAdminUserRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admins.update') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('adminUser'))], 'password' => ['nullable', 'confirmed', Password::defaults()], 'role' => ['required', 'exists:roles,name'], 'is_active' => ['sometimes', 'boolean']];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admins.create') ?? false;
    }

    public function rules(): array
    {
        return ['congregation_id' => $this->congregationRules(), 'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'confirmed', Password::defaults()], 'role' => ['required', 'exists:roles,name'], 'is_active' => ['sometimes', 'boolean']];
    }

    protected function congregationRules(): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('congregations', 'id')->whereNull('deleted_at'),
            Rule::unique('users', 'congregation_id')->ignore($this->route('adminUser')),
        ];
    }

    public function messages(): array
    {
        return [
            'congregation_id.exists' => 'Jemaat yang dipilih tidak tersedia.',
            'congregation_id.unique' => 'Jemaat ini sudah terhubung ke akun admin. Edit akun admin tersebut.',
        ];
    }
}

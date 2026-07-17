<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

final class UpdateCongregationRequest extends StoreCongregationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('congregations.update') ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('congregations', 'email')->ignore($this->route('congregation'))];

        return $rules;
    }
}

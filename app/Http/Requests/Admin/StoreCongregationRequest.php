<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BaptismStatus;
use App\Enums\CongregationGender;
use App\Enums\CongregationMaritalStatus;
use App\Enums\CongregationMembershipStatus;
use App\Enums\MinistryUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCongregationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('congregations.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['children_names', 'siblings_names'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $value = preg_split('/\R/u', $value) ?: [];
            }
            if (is_array($value) && array_is_list($value)) {
                $this->merge([$field => array_values(array_filter(array_map(
                    fn ($name) => is_string($name) ? trim($name) : $name,
                    $value,
                ), fn ($name) => $name !== '' && $name !== null))]);
            }
        }
        if ($this->user()?->hasRole('Super Admin')) {
            $this->merge([
                'is_serving' => $this->input('is_serving', false),
                'ministry_unit' => $this->boolean('is_serving') ? $this->input('ministry_unit') : null,
            ]);
        }
    }

    private function ministryRules(): array
    {
        $isSuperAdmin = $this->user()?->hasRole('Super Admin') ?? false;

        return [
            'is_serving' => [Rule::excludeIf(! $isSuperAdmin), 'required', 'boolean'],
            'ministry_unit' => [Rule::excludeIf(! $isSuperAdmin), 'nullable', Rule::requiredIf($this->boolean('is_serving')), Rule::enum(MinistryUnit::class)],
        ];
    }

    private function profileRules(): array
    {
        return [
            'legacy_username' => ['nullable', 'string', 'max:255'],
            'blood_type' => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
            'last_education' => ['nullable', 'string', 'max:100'],
            'church_origin' => ['nullable', 'string', 'max:255'],
            'reason_to_move_church' => ['nullable', 'string', 'max:2000'],
            'family_status' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'wife_name' => ['nullable', 'string', 'max:255'],
            'husband_name' => ['nullable', 'string', 'max:255'],
            'children_names' => ['nullable', 'array', 'list'],
            'children_names.*' => ['required', 'string', 'max:255'],
            'siblings_names' => ['nullable', 'array', 'list'],
            'siblings_names.*' => ['required', 'string', 'max:255'],
            'holy_spirit_baptism' => ['nullable', 'boolean'],
            'baptism_church' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_serving.boolean' => 'Pilihan Pelayanan tidak valid.',
            'ministry_unit.required' => 'Pilih Unit Pelayanan ketika Pelayanan dicentang.',
            'ministry_unit.Illuminate\\Validation\\Rules\\Enum' => 'Unit Pelayanan yang dipilih tidak valid.',
        ];
    }

    public function rules(): array
    {
        return ['full_name' => ['required', 'string', 'max:255'], 'nickname' => ['nullable', 'string', 'max:100'], 'gender' => ['required', Rule::enum(CongregationGender::class)], 'place_of_birth' => ['nullable', 'string', 'max:100'], 'date_of_birth' => ['nullable', 'date', 'before:today'], 'marital_status' => ['nullable', Rule::enum(CongregationMaritalStatus::class)], 'phone_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'], 'whatsapp_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'], 'email' => ['nullable', 'email', 'max:255', 'unique:congregations,email'], 'address' => ['nullable', 'string'], 'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'], 'postal_code' => ['nullable', 'string', 'max:10'], 'occupation' => ['nullable', 'string', 'max:150'], 'baptism_status' => ['required', Rule::enum(BaptismStatus::class)], 'baptism_date' => ['nullable', 'date'], 'membership_status' => ['required', Rule::enum(CongregationMembershipStatus::class)], 'joined_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string'], 'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'is_active' => ['sometimes', 'boolean']] + $this->ministryRules() + $this->profileRules();
    }
}

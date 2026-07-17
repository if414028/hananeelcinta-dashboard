<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BaptismStatus;
use App\Enums\CongregationGender;
use App\Enums\CongregationMaritalStatus;
use App\Enums\CongregationMembershipStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCongregationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('congregations.create') ?? false;
    }

    public function rules(): array
    {
        return ['full_name' => ['required', 'string', 'max:255'], 'nickname' => ['nullable', 'string', 'max:100'], 'gender' => ['required', Rule::enum(CongregationGender::class)], 'place_of_birth' => ['nullable', 'string', 'max:100'], 'date_of_birth' => ['nullable', 'date', 'before:today'], 'marital_status' => ['nullable', Rule::enum(CongregationMaritalStatus::class)], 'phone_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'], 'whatsapp_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'], 'email' => ['nullable', 'email', 'max:255', 'unique:congregations,email'], 'address' => ['nullable', 'string'], 'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'], 'postal_code' => ['nullable', 'string', 'max:10'], 'occupation' => ['nullable', 'string', 'max:150'], 'baptism_status' => ['required', Rule::enum(BaptismStatus::class)], 'baptism_date' => ['nullable', 'date'], 'membership_status' => ['required', Rule::enum(CongregationMembershipStatus::class)], 'joined_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string'], 'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'is_active' => ['sometimes', 'boolean']];
    }
}

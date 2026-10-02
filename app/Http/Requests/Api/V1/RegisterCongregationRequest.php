<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\BaptismStatus;
use App\Enums\CongregationGender;
use App\Enums\CongregationMaritalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterCongregationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('firebase_token');
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(CongregationGender::class)],
            'nickname' => ['nullable', 'string', 'max:100'],
            'place_of_birth' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'marital_status' => ['nullable', Rule::enum(CongregationMaritalStatus::class)],
            'phone_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'],
            'whatsapp_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'],
            'address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'baptism_status' => ['sometimes', Rule::enum(BaptismStatus::class)],
            'baptism_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'required_if:baptism_status,baptized', 'prohibited_unless:baptism_status,baptized'],
        ];
    }
}

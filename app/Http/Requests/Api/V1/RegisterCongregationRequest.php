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

    protected function prepareForValidation(): void
    {
        $input = $this->input();

        // Optional empty text is omitted, including an unselected baptism status.
        foreach (['nickname', 'whatsapp_number', 'city', 'province', 'postal_code', 'baptism_status', 'baptism_date', 'baptism_church', 'church_origin', 'reason_to_move_church', 'family_status', 'wife_name', 'husband_name'] as $field) {
            if (array_key_exists($field, $input) && ($input[$field] === null || (is_string($input[$field]) && trim($input[$field]) === ''))) {
                unset($input[$field]);
            }
        }

        foreach (['children_names', 'siblings_names'] as $field) {
            if (isset($input[$field]) && is_array($input[$field]) && array_is_list($input[$field])) {
                $input[$field] = array_values(array_filter(array_map(
                    fn ($name) => is_string($name) ? trim($name) : $name,
                    $input[$field],
                ), fn ($name) => $name !== '' && $name !== null));
            }
        }

        if (isset($input['phone_number']) && is_string($input['phone_number']) && str_starts_with($input['phone_number'], '0')) {
            $input['phone_number'] = '+62'.substr($input['phone_number'], 1);
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(CongregationGender::class)],
            'nickname' => ['nullable', 'string', 'max:100'],
            'place_of_birth' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'marital_status' => ['required', Rule::enum(CongregationMaritalStatus::class)],
            'phone_number' => ['required', 'string', 'regex:/^[0-9+() .-]{7,30}$/'],
            'whatsapp_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'],
            'address' => ['required', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'blood_type' => ['required', Rule::in(['A', 'B', 'AB', 'O'])],
            'last_education' => ['required', 'string', 'max:100'],
            'occupation' => ['required', 'string', 'max:150'],
            'baptism_status' => ['sometimes', Rule::enum(BaptismStatus::class)],
            'baptism_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'required_if:baptism_status,baptized', 'prohibited_unless:baptism_status,baptized'],
            'baptism_church' => ['nullable', 'string', 'max:255'],
            'holy_spirit_baptism' => ['nullable', 'boolean'],
            'church_origin' => ['nullable', 'string', 'max:255'],
            'reason_to_move_church' => ['nullable', 'string', 'max:2000'],
            'family_status' => ['nullable', 'string', 'max:100'],
            'wife_name' => ['exclude_if:family_status,Istri', 'nullable', 'string', 'max:255'],
            'husband_name' => ['exclude_if:family_status,Kepala Keluarga', 'nullable', 'string', 'max:255'],
            'children_names' => ['exclude_if:family_status,Anak', 'sometimes', 'array', 'list'],
            'children_names.*' => ['required', 'string', 'max:255'],
            'siblings_names' => ['sometimes', 'array', 'list'],
            'siblings_names.*' => ['required', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg', 'max:5120'],
        ];
    }
}

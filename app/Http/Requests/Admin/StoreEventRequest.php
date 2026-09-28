<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('event') ? 'events.update' : 'events.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_published' => ['sometimes', 'boolean'],
            'registration_fields' => ['required', 'array', 'min:1', 'max:20'],
            'registration_fields.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:50', 'distinct'],
            'registration_fields.*.label' => ['required', 'string', 'max:100'],
            'registration_fields.*.type' => ['required', Rule::in(['text', 'email', 'tel', 'date', 'select', 'textarea'])],
            'registration_fields.*.required' => ['nullable', 'boolean'],
            'registration_fields.*.options' => ['nullable', 'array', 'max:30'],
            'registration_fields.*.options.*' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return ['registration_fields.required' => 'Tambahkan minimal satu field pendaftaran.', 'registration_fields.*.key.distinct' => 'Key setiap field harus unik.'];
    }
}

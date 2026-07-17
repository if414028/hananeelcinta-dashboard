<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Enums\PrayerRequestCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePrayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone_number' => ['nullable', 'regex:/^[0-9+() .-]{7,30}$/'], 'prayer_category' => ['required', Rule::enum(PrayerRequestCategory::class)], 'prayer_content' => ['required', 'string', 'min:10', 'max:5000'], 'is_anonymous' => ['sometimes', 'boolean'], 'is_confidential' => ['sometimes', 'boolean'], 'privacy_accepted' => ['accepted'], 'website' => ['prohibited']];
    }

    public function messages(): array
    {
        return ['name.required' => 'Nama wajib diisi.', 'email.email' => 'Format email tidak valid.', 'prayer_category.required' => 'Kategori doa wajib dipilih.', 'prayer_content.required' => 'Isi prayer request wajib diisi.', 'prayer_content.min' => 'Isi prayer request minimal 10 karakter.', 'privacy_accepted.accepted' => 'Anda harus menyetujui kebijakan privasi.'];
    }
}

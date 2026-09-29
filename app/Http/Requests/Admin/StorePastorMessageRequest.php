<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePastorMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pastor_messages.create') ?? false;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'writer' => ['nullable', 'string', 'max:255'], 'content' => ['required', 'string', function ($attribute, $value, $fail): void {
            if (trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\xc2\xa0") === '') {
                $fail('Konten wajib diisi.');
            }
        }], 'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']];
    }
}

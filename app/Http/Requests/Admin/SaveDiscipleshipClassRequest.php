<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveDiscipleshipClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('discipleshipClass') ? 'discipleship.update' : 'discipleship.create') ?? false;
    }

    public function rules(): array
    {
        $imageRequired = $this->route('discipleshipClass') ? 'nullable' : 'required';

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'teacher_name' => ['required', 'string', 'max:255'],
            'banner' => [$imageRequired, 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'badge' => [$imageRequired, 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['required', 'integer', 'distinct', Rule::exists('congregations', 'id')->whereNull('deleted_at')],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama kelas', 'description' => 'deskripsi kelas', 'teacher_name' => 'nama pengajar', 'banner' => 'banner kelas', 'badge' => 'badge kelas', 'starts_at' => 'tanggal mulai', 'ends_at' => 'tanggal selesai', 'participant_ids' => 'peserta kelas', 'participant_ids.*' => 'peserta kelas'];
    }

    public function messages(): array
    {
        return [
            'participant_ids.required' => 'Pilih minimal satu jemaat untuk mengikuti kelas.',
            'participant_ids.*.exists' => 'Salah satu jemaat sudah tidak tersedia. Periksa kembali peserta kelas.',
            'ends_at.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
        ];
    }
}

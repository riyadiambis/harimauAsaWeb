<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class MasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Input di-lowercase supaya cocok dengan cara username disimpan (A-10) —
     * mutator di model hanya bekerja saat menulis, tidak saat mencari.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => Str::lower((string) $this->input('username'))]);
        }
    }

    /**
     * Sengaja TIDAK memakai pola A-1 di sini. Kalau username berformat salah
     * ditolak validasi, pesannya akan berbeda dari A-5 dan itu membocorkan
     * username mana yang mungkin terdaftar. Cukup pastikan terisi.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'ingat_saya' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }
}

<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DaftarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Huruf besar dinormalkan jadi lowercase sebelum divalidasi (A-10), jadi
     * "Riyadi" diterima dan tersimpan sebagai "riyadi". Spasi dan karakter lain
     * tetap ditolak karena tidak lolos pola A-1.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => Str::lower((string) $this->input('username'))]);
        }
    }

    /**
     * Tepat empat field sesuai A-2 — nama, username, sandi, konfirmasi sandi.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            // Rule::unique menanyakan tabel langsung tanpa scope soft delete, jadi
            // username milik akun yang sudah dihapus tetap terhitung terpakai —
            // sama seperti unique index di database.
            'username' => [...User::aturanUsername(), Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 4 karakter.',
            'username.max' => 'Username maksimal 20 karakter.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, dan garis bawah (_).',
            'username.unique' => 'Username ini sudah dipakai. Coba yang lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
}

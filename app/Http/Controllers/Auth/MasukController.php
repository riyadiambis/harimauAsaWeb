<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MasukRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasukController extends Controller
{
    public function create(): View
    {
        return view('auth.masuk');
    }

    public function store(MasukRequest $request): RedirectResponse
    {
        $user = User::where('username', $request->validated('username'))->first();

        // A-5: "username tidak terdaftar" dan "kata sandi salah" harus menghasilkan
        // pesan yang sama persis. Digabung dalam satu cabang supaya tidak mungkin
        // tanpa sengaja jadi dua pesan berbeda saat kode ini diubah nanti.
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'Username atau kata sandi salah.',
            ]);
        }

        // A-6 dan status tidak aktif. Diperiksa setelah kata sandi benar, jadi
        // bukan alat menebak username mana yang terdaftar.
        $penolakan = match ($user->member?->status) {
            'pending' => 'Akun kamu masih menunggu persetujuan pengurus.',
            'non_aktif', 'alumni' => 'Akun kamu sedang tidak aktif. Hubungi pengurus kalau ini keliru.',
            default => null,
        };

        if ($penolakan !== null) {
            throw ValidationException::withMessages(['username' => $penolakan]);
        }

        Auth::login($user, $request->boolean('ingat_saya'));

        $request->session()->regenerate();

        // Kalau harus_ganti_sandi true, middleware PastikanSandiSudahDiganti yang
        // membelokkan ke /ganti-sandi pada request berikutnya (A-8).
        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk');
    }
}

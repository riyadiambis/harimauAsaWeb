<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GantiSandiRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GantiSandiController extends Controller
{
    public function edit(): View
    {
        return view('auth.ganti-sandi');
    }

    public function update(GantiSandiRequest $request): RedirectResponse
    {
        $user = $request->user();

        // harus_ganti_sandi sengaja di luar #[Fillable] (kolom hak akses tidak
        // boleh tertembus mass-assignment), jadi diset langsung di sini.
        // Cast 'hashed' tetap berlaku pada penetapan properti biasa.
        $user->password = $request->validated('password');
        $user->harus_ganti_sandi = false;
        $user->save();

        return redirect('/')->with('status', 'Kata sandi berhasil diganti.');
    }
}

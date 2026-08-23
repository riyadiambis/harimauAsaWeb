<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DaftarRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DaftarController extends Controller
{
    public function create(): View
    {
        return view('auth.daftar');
    }

    public function store(DaftarRequest $request): RedirectResponse
    {
        // Transaksi supaya tidak pernah ada user tanpa baris member — akun tanpa
        // member berarti akun tanpa status, dan pemeriksaan pending jadi bocor.
        DB::transaction(function () use ($request): void {
            $user = User::create($request->safe()->only(['nama', 'username', 'password']));

            $user->member()->create([
                'status' => 'pending',
                'tanggal_gabung' => now(),
            ]);
        });

        // A-4: JANGAN auto-login. Pendaftar diarahkan ke halaman konfirmasi.
        return redirect()->route('daftar.selesai');
    }

    public function selesai(): View
    {
        return view('auth.daftar-selesai');
    }
}

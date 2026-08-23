<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanSandiSudahDiganti
{
    /**
     * A-8: akun yang sandinya baru direset pengurus tidak bisa ke halaman lain
     * sampai menggantinya. Halaman ganti sandi sendiri dan tombol keluar tetap
     * boleh diakses — tanpa pengecualian itu, pengguna terkunci total.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->harus_ganti_sandi && ! $request->routeIs('ganti-sandi.*', 'keluar')) {
            return redirect()->route('ganti-sandi.edit');
        }

        return $next($request);
    }
}

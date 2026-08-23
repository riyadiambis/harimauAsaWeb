<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CekUsernameController extends Controller
{
    /**
     * Cek ketersediaan username untuk form pendaftaran (A-1).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $username = Str::lower((string) $request->query('username', ''));

        $valid = Validator::make(
            ['username' => $username],
            ['username' => User::aturanUsername()],
        )->passes();

        // withTrashed(): username milik akun yang di-soft-delete masih memegang
        // unique index, jadi tidak boleh dilaporkan tersedia — kalau tidak, form
        // menjanjikan sesuatu yang akan ditolak database saat submit.
        $tersedia = $valid && ! User::withTrashed()->where('username', $username)->exists();

        return response()->json([
            'username' => $username,
            'valid' => $valid,
            'tersedia' => $tersedia,
        ]);
    }
}

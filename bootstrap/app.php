<?php

use App\Http\Middleware\PastikanSandiSudahDiganti;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sengaja TIDAK dipasang di seluruh grup web — halaman publik tetap bisa
        // dibuka. Ditempelkan per rute yang butuh login (A-8).
        $middleware->alias([
            'sandi.diganti' => PastikanSandiSudahDiganti::class,
        ]);

        // Halaman masuk aplikasi ini /masuk, bukan /login bawaan Laravel.
        $middleware->redirectGuestsTo(fn () => route('masuk'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

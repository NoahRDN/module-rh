<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/bonjour', function () {
    return view('bonjour');
});

Route::get('/internal/scheduler/{token}', function (string $token) {
    abort_unless(
        hash_equals((string) env('SCHEDULER_TOKEN', ''), $token),
        403,
    );

    Artisan::call('schedule:run');

    return response()->json([
        'ok' => true,
        'ran_at' => now()->toIso8601String(),
        'output' => trim(Artisan::output()),
    ]);
});

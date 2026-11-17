<?php

use App\Http\Controllers\PlatformController as P;
use Illuminate\Support\Facades\Route;

Route::get('/', [P::class, 'home']);
require __DIR__.'/auth.php';
require __DIR__.'/learning.php';
require __DIR__.'/commerce.php';
Route::get('/u/{username}', [P::class, 'profile']);
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [P::class, 'dashboard']);
    Route::get('/dashboard/kelas', [P::class, 'dashboard']);
    Route::get('/dashboard/leaderboard', [P::class, 'leaderboard']);
    Route::get('/admin/users', [P::class, 'adminUsers']);
    Route::put('/admin/users/{user}/role', [P::class, 'adminRole']);
});
require __DIR__.'/content.php';

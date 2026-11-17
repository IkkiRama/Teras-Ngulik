<?php

use App\Http\Controllers\AuthController as A;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', fn (Request $r) => app(A::class)->page($r, 'login'))->name('login');
    Route::post('/login', [A::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', fn (Request $r) => app(A::class)->page($r, 'register'))->name('register');
    Route::post('/register', [A::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', fn (Request $r) => app(A::class)->page($r, 'forgot-password'))->name('password.request');
    Route::post('/forgot-password', [A::class, 'forgot'])->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', fn (Request $r) => app(A::class)->page($r, 'reset-password'))->name('password.reset');
    Route::post('/reset-password', [A::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
    Route::get('/auth/google', [A::class, 'google']);
    Route::get('/auth/google/callback', [A::class, 'googleCallback']);
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [A::class, 'logout'])->name('logout');
    Route::get('/verify-email', fn (Request $r) => app(A::class)->page($r, 'verify-email'))->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [A::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [A::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');
    Route::get('/dashboard/profil', [A::class, 'profile']);
    Route::get('/dashboard/pengaturan', [A::class, 'profile']);
    Route::put('/dashboard/profil', [A::class, 'updateProfile']);
    Route::get('/account/security', fn (Request $r) => app(A::class)->page($r, 'security'));
    Route::post('/account/password/otp', [A::class, 'otp'])->middleware(['verified', 'throttle:3,10']);
    Route::put('/account/password', [A::class, 'changePassword'])->middleware(['verified', 'throttle:10,10']);
    Route::get('/account/export',[A::class, 'export'])->middleware('verified');
});

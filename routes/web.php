<?php

use App\Http\Middleware\Auth\MustBeAdmin;
use App\Http\Middleware\Auth\MustBeSuperAdmin;
use Illuminate\Support\Facades\Route;

// Health check endpoint for monitoring
Route::get('ping', fn () => 'pong');

Route::group([], __DIR__ . '/web/auth.php');

Route::middleware('auth', MustBeSuperAdmin::class)->group(__DIR__ . '/web/superadmins.php');
Route::middleware('auth', MustBeAdmin::class)->group(__DIR__ . '/web/admins.php');
Route::middleware('auth', 'verified')->group(__DIR__ . '/web/members.php');

Route::get('playground', fn () => inertia('Playground'))->name('playground');

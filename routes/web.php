<?php

use App\Http\Middleware\Auth\MustBeAdmin;
use App\Http\Middleware\Auth\MustBeSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => 'pong');

Route::group([], __DIR__ . '/web/auth.php');
Route::middleware('auth', 'verified')->group(__DIR__ . '/web/members.php');
Route::middleware('auth', 'verified', MustBeAdmin::class)->group(__DIR__ . '/web/admins.php');
Route::middleware('auth', MustBeSuperAdmin::class)->group(__DIR__ . '/web/superadmins.php');

// Development routes (only in non-production environments)
if (app()->environment('local', 'testing')) {
    Route::group([], __DIR__ . '/web/dev.php');
}

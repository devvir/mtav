<?php

// Copilot - pending review

use App\Http\Controllers\Auth\ConfirmMemberAccount;
use App\Http\Middleware\Auth\MustBeAdmin;
use App\Http\Middleware\Auth\MustBeSuperAdmin;
use Illuminate\Support\Facades\Route;

// Health check endpoint for monitoring
Route::get('ping', fn () => 'pong');

// DEV ONLY: Test email endpoint - REMOVE IN PRODUCTION
if (app()->environment('local')) {
    Route::get('dev/test-emails', [App\Http\Controllers\Dev\TestEmailController::class, 'sendTestEmails']);
    Route::get('dev/ui', App\Http\Controllers\Dev\UiController::class)->name('dev.ui');
}

Route::group([], __DIR__ . '/web/auth.php');

Route::get('join', [ConfirmMemberAccount::class, 'show'])->name('join.show');
Route::post('join', [ConfirmMemberAccount::class, 'store'])->name('join.store');

Route::middleware('auth', MustBeSuperAdmin::class)->group(__DIR__ . '/web/superadmins.php');
Route::middleware('auth', MustBeAdmin::class)->group(__DIR__ . '/web/admins.php');
Route::middleware('auth', 'verified')->group(__DIR__ . '/web/members.php');

Route::get('playground', fn () => inertia('Playground'))->name('playground');

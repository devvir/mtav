<?php

use App\Http\Controllers\Auth\InvitationController;
use App\Http\Middleware\Auth\ProjectMustBeSelected;
use Illuminate\Support\Facades\Route;

/** Healthcheck Route */
Route::get('ping', fn () => 'pong');

/** Documentation Routes (accessible to everyone) */
Route::group([], __DIR__ . '/web/documentation.php');

/** User Invitation Routes */
Route::get('invitation', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('invitation', [InvitationController::class, 'store'])->name('invitation.store');

/** Guest-only Routes */
Route::middleware('guest')->group(__DIR__ . '/web/guest.php');

/** Auth-handling Routes (password reset, email verification, etc.) */
Route::middleware('auth')->group(__DIR__ . '/web/auth.php');

/** User Settings Routes */
Route::middleware('auth', 'verified', 'invitation.accepted')->group(__DIR__ . '/web/settings.php');

/** Project-context Routes */
Route::middleware('auth', 'verified', 'invitation.accepted')->group(__DIR__ . '/web/context.php');

/** Project-scoped Routes and set/unset Current Project endpoints */
Route::middleware('auth', ProjectMustBeSelected::class)->group(__DIR__ . '/web/project.php');

/** General User Routes (Members, Admins and Superadmins) */
Route::middleware('auth', 'verified', 'invitation.accepted')->group(__DIR__ . '/web/users.php');

/** Development Routes (only in development environments) */
if (app()->environment('local', 'testing')) {
    Route::group([], __DIR__ . '/web/dev.php');
}

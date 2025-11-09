<?php

use App\Http\Controllers\Auth\InvitationController;
use App\Http\Middleware\Auth\ProjectMustBeSelected;
use Illuminate\Support\Facades\Route;

/** Healthcheck Route */
Route::get('ping', fn () => 'pong');

/** User Invitation Routes */
Route::get('invitation', [InvitationController::class, 'edit'])->name('invitation.edit');
Route::post('invitation', [InvitationController::class, 'update'])->name('invitation.update');

/** Guest-only Routes */
Route::middleware('guest')->group(__DIR__ . '/web/guest.php');

/** Auth-handling Routes (password reset, email verification, etc.) */
Route::middleware('auth')->group(__DIR__ . '/web/auth.php');

/** Documentation Routes */
Route::middleware('auth')->group(__DIR__ . '/web/documentation.php');

/** Development Routes */
Route::middleware('auth')->group(__DIR__ . '/web/dev.php');

/** User Settings Routes */
Route::middleware('auth', 'verified')->group(__DIR__ . '/web/settings.php');

/** Project-context Routes */
Route::middleware('auth', 'verified')->group(__DIR__ . '/web/context.php');

/** Project-scoped Routes and set/unset Current Project endpoints */
Route::middleware('auth', 'verified', ProjectMustBeSelected::class)->group(__DIR__ . '/web/project.php');

/** General User Routes (Members, Admins and Superadmins) */
Route::middleware('auth', 'verified')->group(__DIR__ . '/web/users.php');

/** Development Routes (only in development environments) */
if (app()->environment('local', 'testing')) {
    Route::middleware('auth')->group(__DIR__ . '/web/dev.php');
}

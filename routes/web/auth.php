<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\RefreshCsrfTokenController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::post('csrf-token', RefreshCsrfTokenController::class);

Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
    ->name('verification.verify')
    ->middleware('signed', 'throttle:6,1');
Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->name('verification.send')
    ->middleware('throttle:6,1');

Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

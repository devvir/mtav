<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RefreshCsrfTokenController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::post('csrf-token', RefreshCsrfTokenController::class);

Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->name('verification.verify')
    ->middleware('signed', 'throttle:6,1');

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

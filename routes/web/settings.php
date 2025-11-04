<?php

use App\Http\Controllers\Settings\AvatarController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('settings', '/settings/profile');

Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

Route::post('settings/avatar', [AvatarController::class, 'update'])->name('avatar.update');

Route::get('settings/appearance', fn () => inertia('Settings/Appearance'))->name('appearance');

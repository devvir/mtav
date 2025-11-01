<?php

use App\Http\Controllers\ContactAdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\Resources\AdminController;
use App\Http\Controllers\Resources\FamilyController;
use App\Http\Controllers\Resources\LogController;
use App\Http\Controllers\Resources\MediaController;
use App\Http\Controllers\Resources\MemberController;
use App\Http\Controllers\Resources\ProjectController;
use App\Http\Controllers\Resources\UnitController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Middleware\Auth\ProjectMustBeSelected;
use Illuminate\Support\Facades\Route;

Route::resource('projects', ProjectController::class)
    ->only('show')
    ->whereNumber('project');

Route::resource('admins', AdminController::class)
    ->only('index', 'show')
    ->whereNumber('admin');

Route::resource('members', MemberController::class)
    ->only('index', 'show', 'create', 'store')
    ->whereNumber('member');

Route::resource('families', FamilyController::class)
    ->only('index', 'show', 'edit', 'update')
    ->whereNumber('family');

Route::resource('media', MediaController::class);

/**
 * Project-based pages
 */
Route::middleware(ProjectMustBeSelected::class)->group(function () {
    Route::get('/', DashboardController::class)->name('home');
    Route::get('gallery', GalleryController::class)->name('gallery');

    Route::resource('units', UnitController::class)
        ->only('index', 'show')
        ->whereNumber('unit');

    Route::resource('logs', LogController::class)
        ->only('index', 'show')
        ->whereNumber('log');

    Route::get('admins/{admin}/contact', [ContactAdminController::class, 'create'])
        ->name('admins.contact');
});

/**
 * Current User's Settings
 */
Route::redirect('settings', '/settings/profile');

Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

Route::get('settings/appearance', fn () => inertia('Settings/Appearance'))->name('appearance');

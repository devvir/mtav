<?php

use App\Http\Controllers\Resources\AdminController;
use App\Http\Controllers\Resources\FamilyController;
use App\Http\Controllers\Resources\LogController;
use App\Http\Controllers\Resources\MemberController;
use App\Http\Controllers\Resources\ProjectController;
use App\Http\Middleware\Auth\MustBeSuperAdmin;
use Illuminate\Support\Facades\Route;

/** Superadmin Routes */
Route::middleware(MustBeSuperAdmin::class)->group(function () {
    Route::resource('projects', ProjectController::class)->only('create', 'store');
    Route::resource('admins', AdminController::class)->only('restore');
});

/** Member / Admin Routes */
Route::resource('projects', ProjectController::class)->except('create', 'store');
Route::resource('families', FamilyController::class);
Route::resource('members', MemberController::class);
Route::resource('admins', AdminController::class)->except('restore');
Route::resource('logs', LogController::class)->only('index', 'show');

Route::get('media', fn () => 'Not yet implemented')->name('media.create');

<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Resources\AdminController;
use App\Http\Controllers\Resources\EventController;
use App\Http\Controllers\Resources\FamilyController;
use App\Http\Controllers\Resources\ProjectController;
use App\Http\Controllers\Resources\UnitController;
use App\Http\Controllers\Resources\UserController;
use App\Http\Controllers\SetCurrentProjectController;
use App\Http\Middleware\Auth\ProjectMustBeSelected;
use Illuminate\Support\Facades\Route;

Route::post('projects/current/{project}', [SetCurrentProjectController::class, 'set'])->name('setCurrentProject');
Route::delete('projects/current/unset', [SetCurrentProjectController::class, 'unset'])->name('resetCurrentProject');

Route::resource('projects', ProjectController::class)->only('index', 'edit', 'update', 'destroy');

Route::resource('admins', AdminController::class)->only('create', 'store', 'edit', 'update', 'destroy');

Route::resource('users', UserController::class)->only('edit', 'update', 'destroy');

Route::resource('events', EventController::class);

/**
 * Project-based pages
 */
Route::middleware(ProjectMustBeSelected::class)->group(function () {
    Route::resource('families', FamilyController::class)->only('create', 'store', 'destroy');
    Route::resource('units', UnitController::class)->except('index', 'show');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

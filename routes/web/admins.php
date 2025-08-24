<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Resources\ProjectController;
use App\Http\Controllers\Resources\UnitController;
use App\Http\Controllers\Resources\UserController;
use App\Http\Controllers\SetCurrentProjectController;
use App\Http\Middleware\Auth\ProjectMustBeSelected;
use Illuminate\Support\Facades\Route;

Route::resource('projects', ProjectController::class)->only('index', 'edit', 'update');
Route::post('projects/{project}', SetCurrentProjectController::class)->name('setCurrentProject');

Route::resource('users', UserController::class)->only('create', 'store', 'edit', 'update', 'destroy');

/**
 * Project-based pages
 */
Route::middleware(ProjectMustBeSelected::class)->group(function () {
    Route::resource('families', UserController::class)->only('create', 'store', 'destroy');
    Route::resource('units', UnitController::class)->except('index', 'show');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

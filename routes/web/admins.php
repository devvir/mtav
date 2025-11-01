<?php

use App\Http\Controllers\Resources\AdminController;
use App\Http\Controllers\Resources\EventController;
use App\Http\Controllers\Resources\FamilyController;
use App\Http\Controllers\Resources\MemberController;
use App\Http\Controllers\Resources\ProjectController;
use App\Http\Controllers\Resources\UnitController;
use App\Http\Controllers\Resources\UnitTypeController;
use App\Http\Controllers\SetCurrentProjectController;
use App\Http\Middleware\Auth\ProjectMustBeSelected;
use Illuminate\Support\Facades\Route;

Route::post('projects/current/{project}', [SetCurrentProjectController::class, 'set'])
    ->name('setCurrentProject');
Route::delete('projects/current/unset', [SetCurrentProjectController::class, 'unset'])
    ->name('resetCurrentProject');

Route::resource('projects', ProjectController::class)->only('index', 'edit', 'update', 'destroy');

Route::resource('admins', AdminController::class)->only('create', 'store', 'edit', 'update', 'destroy');

Route::resource('families', FamilyController::class)->only('create', 'store', 'destroy');
Route::resource('members', MemberController::class)->only('edit', 'update', 'destroy');

/**
 * Project-based pages
 */
Route::middleware(ProjectMustBeSelected::class)->group(function () {
    Route::resource('events', EventController::class);
    Route::resource('units', UnitController::class)->except('index', 'show');
    Route::resource('unit-types', UnitTypeController::class);
});

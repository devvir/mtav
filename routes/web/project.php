<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Resources\EventController;
use App\Http\Controllers\Resources\UnitController;
use App\Http\Controllers\Resources\UnitTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('gallery', [MediaController::class, 'index'])->name('gallery');
Route::get('contact/{admin}', [ContactController::class, 'create'])->name('contact');

Route::resource('media', MediaController::class)->parameter('media', 'media'); // NOT medium
Route::resource('events', EventController::class);
Route::resource('units', UnitController::class);
Route::resource('unit_types', UnitTypeController::class);

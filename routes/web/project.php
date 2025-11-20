<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LotteryController;
use App\Http\Controllers\Resources\AudioController;
use App\Http\Controllers\Resources\DocumentController;
use App\Http\Controllers\Resources\EventController;
use App\Http\Controllers\Resources\MediaController;
use App\Http\Controllers\Resources\PlanController;
use App\Http\Controllers\Resources\UnitController;
use App\Http\Controllers\Resources\UnitTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('gallery', [MediaController::class, 'index'])->name('gallery');
Route::get('contact/{admin}', [ContactController::class, 'create'])->name('contact');

Route::resource('audios', AudioController::class)->only('index', 'create');
Route::resource('documents', DocumentController::class)->only('index', 'create');
Route::resource('events', EventController::class);
Route::resource('media', MediaController::class)->parameter('media', 'media'); // NOT medium
Route::resource('plans', PlanController::class)->only('show', 'edit', 'update');
Route::resource('units', UnitController::class);
Route::resource('unit_types', UnitTypeController::class);

Route::get('lottery', [LotteryController::class, 'index'])->name('lottery');
Route::patch('lottery/{lottery}', [LotteryController::class, 'update'])->name('lottery.update');
Route::post('lottery/preferences', [LotteryController::class, 'preferences'])->name('lottery.preferences');

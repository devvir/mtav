<?php

use App\Http\Controllers\Dev\EntityCardsController;
use App\Http\Controllers\Dev\FlashController;
use App\Http\Controllers\Dev\PlanController;
use App\Http\Controllers\Dev\UiController;
use Illuminate\Support\Facades\Route;

Route::get('dev', fn () => inertia('Dev/Dashboard'))->name('dev.dashboard');

Route::get('dev/ui', UiController::class)->name('dev.ui');
Route::get('dev/filters', fn () => inertia('Dev/Filters'))->name('dev.filters');
Route::get('dev/cards', fn () => inertia('Dev/Cards'))->name('dev.cards');
Route::get('dev/entity-cards', EntityCardsController::class)->name('dev.entity-cards');
Route::get('dev/plans', PlanController::class)->name('dev.plans');
Route::get('dev/playground', fn () => inertia('Dev/Playground'))->name('dev.playground');

// Flash message testing
Route::get('dev/flash', [FlashController::class, 'index'])->name('dev.flash');
Route::post('dev/flash/send', [FlashController::class, 'send'])->name('dev.flash.send');
Route::get('dev/flash/all', [FlashController::class, 'all'])->name('dev.flash.all');

<?php

use App\Http\Controllers\Dev\EntityCardsController;
use App\Http\Controllers\Dev\FlashController;
use App\Http\Controllers\Dev\FormController;
use App\Http\Controllers\Dev\PlanController;
use App\Http\Controllers\Dev\UiController;
use Illuminate\Support\Facades\Route;

Route::get('', fn () => inertia('Dev/Dashboard'))->name('dev.dashboard');

Route::get('ui', UiController::class)->name('dev.ui');
Route::get('filters', fn () => inertia('Dev/Filters'))->name('dev.filters');
Route::get('forms', FormController::class)->name('dev.forms');
Route::get('cards', fn () => inertia('Dev/Cards'))->name('dev.cards');
Route::get('entity-cards', EntityCardsController::class)->name('dev.entity-cards');
Route::get('plans', PlanController::class)->name('dev.plans');
Route::get('playground', fn () => inertia('Dev/Playground'))->name('dev.playground');

// Flash message testing
Route::get('flash', [FlashController::class, 'index'])->name('dev.flash');
Route::post('flash/send', [FlashController::class, 'send'])->name('dev.flash.send');
Route::get('flash/all', [FlashController::class, 'all'])->name('dev.flash.all');

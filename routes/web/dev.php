<?php

use App\Http\Controllers\Dev\UiController;
use Illuminate\Support\Facades\Route;

Route::get('dev', fn () => inertia('Dev/Dashboard'))->name('dev.dashboard');

Route::get('dev/ui', UiController::class)->name('dev.ui');
Route::get('dev/cards', fn () => inertia('Dev/Cards'))->name('dev.cards');
Route::get('dev/entity-cards', fn () => inertia('Dev/EntityCards'))->name('dev.entity-cards');
Route::get('dev/playground', fn () => inertia('Dev/Playground'))->name('dev.playground');

// Flash message testing
Route::get('dev/flash', fn () => inertia('Dev/FlashTester'))->name('dev.flash');
Route::post(
    'dev/flash/send',
    fn () => to_route('dev.flash')->with(request('type'), request('message'))
)->name('dev.flash.send');
Route::get(
    'dev/flash/all',
    fn () => redirect()->route('dev.flash')
        ->with('success', 'Operation completed successfully!')
        ->with('info', 'Here\'s some additional information.')
        ->with('warning', 'Please note this important warning.')
        ->with('error', 'An error occurred during processing.')
)->name('dev.flash.all');

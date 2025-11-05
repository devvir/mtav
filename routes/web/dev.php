<?php

use App\Http\Controllers\Dev\UiController;
use Illuminate\Support\Facades\Route;

// Dev Dashboard
Route::get('dev', fn () => inertia('Dev/DevDashboard'))->name('dev.dashboard');

Route::get('playground', fn () => inertia('Playground'))->name('playground');
Route::get('dev/ui', UiController::class)->name('dev.ui');

// Flash message testing
Route::get('dev/flash', fn () => inertia('Dev/FlashTester'))->name('dev.flash');
Route::post('dev/flash/send', function () {
    $type = request('type');
    $message = request('message');

    return redirect()->route('dev.flash')->with($type, $message);
})->name('dev.flash.send');

Route::get(
    'dev/flash/all',
    fn () => redirect()->route('dev.flash')
        ->with('success', 'Operation completed successfully!')
        ->with('info', 'Here\'s some additional information.')
        ->with('warning', 'Please note this important warning.')
        ->with('error', 'An error occurred during processing.')
)->name('dev.flash.all');

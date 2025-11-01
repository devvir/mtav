<?php

use App\Http\Controllers\Dev\UiController;
use Illuminate\Support\Facades\Route;

Route::get('playground', fn () => inertia('Playground'))->name('playground');
Route::get('dev/ui', UiController::class)->name('dev.ui');

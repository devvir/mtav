<?php

use App\Http\Controllers\CurrentProjectController;
use Illuminate\Support\Facades\Route;

Route::post('projects/current/{project}', [CurrentProjectController::class, 'set'])
    ->name('setCurrentProject');
Route::delete('projects/current/unset', [CurrentProjectController::class, 'unset'])
    ->name('resetCurrentProject');

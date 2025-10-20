<?php

use App\Http\Controllers\Resources\ProjectController;
use Illuminate\Support\Facades\Route;

Route::resource('projects', ProjectController::class)->only('create', 'store');

<?php

use App\Http\Controllers\Resources\AdminController;
use App\Http\Controllers\Resources\ProjectController;
use Illuminate\Support\Facades\Route;

Route::resource('projects', ProjectController::class)->only('create', 'store', 'destroy');
Route::resource('admins', AdminController::class)->only('create', 'store', 'edit', 'update', 'destroy');

<?php

use App\Http\Controllers\DocumentationController;
use Illuminate\Support\Facades\Route;

// Documentation routes (accessible to everyone - guests and authenticated users)
Route::get('documentation/faq', [DocumentationController::class, 'faq'])->name('documentation.faq');
Route::get('documentation/guide', [DocumentationController::class, 'guide'])->name('documentation.guide');

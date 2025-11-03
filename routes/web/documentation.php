<?php

use App\Http\Controllers\DocumentationController;
use Illuminate\Support\Facades\Route;

// Documentation routes (accessible to everyone - guests and authenticated users)
Route::get('documentation/faq/{role?}', [DocumentationController::class, 'faq'])->name('documentation.faq');
Route::get('documentation/guide/{role?}', [DocumentationController::class, 'guide'])->name('documentation.guide');

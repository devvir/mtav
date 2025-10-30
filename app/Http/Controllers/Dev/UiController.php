<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Inertia\Response;

class UiController extends Controller
{
    /**
     * Show the UI design system preview page.
     */
    public function __invoke(): Response
    {
        return inertia('Dev/Ui');
    }
}

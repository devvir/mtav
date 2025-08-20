<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\ResourceController;
use Illuminate\Http\Request;

class HomeController extends ResourceController
{
    /**
     * Show the current Project's Dashboard.
     */
    public function __invoke(Request $request)
    {
        return inertia('Dashboard');
    }
}

<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\ResourceController;

class DashboardController extends ResourceController
{
    /**
     * Show the current Project's Dashboard.
     */
    public function __invoke()
    {
        return inertia('Dashboard');
    }
}

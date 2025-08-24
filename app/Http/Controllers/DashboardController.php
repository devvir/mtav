<?php

namespace App\Http\Controllers;

class DashboardController
{
    /**
     * Show the current Project's Dashboard.
     */
    public function __invoke()
    {
        return inertia('Dashboard');
    }
}

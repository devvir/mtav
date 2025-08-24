<?php

namespace App\Http\Controllers\Resources;

use App\Models\Log;
use Inertia\Response;

class LogController extends Controller
{
    /**
     * Show the members dashboard.
     */
    public function index(): Response
    {
        return inertia('Logs/Index');
    }

    /**
     * Show the project details.
     */
    public function show(Log $log): Response
    {
        return inertia('Logs/Show', compact('log'));
    }
}

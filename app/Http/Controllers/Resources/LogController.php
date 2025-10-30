<?php

// Copilot - pending review

namespace App\Http\Controllers\Resources;

use App\Models\Log;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    /**
     * Show the logs list.
     */
    public function index(): Response
    {
        $logs = Log::with('user')
            ->where('project_id', currentProject()->id)
            ->latest()
            ->get();

        return inertia('Logs/Index', [
            'logs' => Inertia::deepMerge(fn () => $logs),
        ]);
    }

    /**
     * Show the log details.
     */
    public function show(Log $log): Response
    {
        $log->load('user');

        return inertia('Logs/Show', compact('log'));
    }
}

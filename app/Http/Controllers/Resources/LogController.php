<?php

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
        $logs = Log::with('user')->whereProjectId(currentProjectId())->latest();

        return inertia('Logs/Index', [
            'logs' => Inertia::deepMerge(fn () => $logs->paginate(30)),
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

<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\FilteredIndexRequest;
use App\Models\Log;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    /**
     * Show the logs list.
     */
    public function index(FilteredIndexRequest $request): Response
    {
        $logs = Log::latest()
            ->with('user')
            ->when($request->project_id, fn ($q, int $id) => $q->inProject($id))
            ->when($request->q, fn ($q, $search) => $q->search($search));

        return inertia('Logs/Index', [
            'logs' => Inertia::deepMerge(fn () => $logs->paginate(30)),
            'q' => $request->q ?? '',
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

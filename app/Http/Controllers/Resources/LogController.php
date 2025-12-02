<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\FilteredIndexRequest;
use App\Models\Log;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $logs = Log::latest()
            ->with('creator')
            ->when($request->project_id, fn ($q, int $id) => $q->inProject($id))
            ->when($request->q, fn ($q, $search) => $q->search($search));

        return inertia('Logs/Index', [
            'logs' => Inertia::deepMerge(fn () => $logs->paginate(30)),
            'q'    => $request->q ?? '',
        ]);
    }

    public function show(Log $log): Response
    {
        return inertia('Logs/Show', [
            'log' => $log->load('creator', 'project'),
        ]);
    }
}

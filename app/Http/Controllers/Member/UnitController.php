<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Http\Resources\UnitTypeResource;
use App\Models\Project;
use App\Models\Unit;
use Inertia\Response;

/**
 * The project's units, by type.
 */
class UnitController extends Controller
{
    public function index(Project $project): Response
    {
        return inertia('Member/Units/Index', [
            'unitTypes' => UnitTypeResource::collection(
                $project->unitTypes()->with(['units' => fn ($q) => $q->alphabetically()->with('family:id,name')])
                    ->withCount('families')->alphabetically()->get()
            ),
        ]);
    }

    public function show(Project $project, Unit $unit): Response
    {
        return inertia('Member/Units/Show', ['unit' => UnitResource::make($unit->load('type', 'family'))]);
    }
}

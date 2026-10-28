<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Http\Resources\UnitResource;
use App\Http\Resources\UnitTypeResource;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The project's units, by type, and where they are on the plan; admins add, change and
 * remove types and units, and draw the plan, on these same pages.
 */
class UnitController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        return inertia('Member/Units/Index', [
            'unitTypes' => UnitTypeResource::collection(
                $project->unitTypes()->with(['units' => fn ($q) => $q->alphabetically()->with('family:id,name')])
                    ->withCount('families')->alphabetically()->get()
            ),
            /** Where they are: after the list */
            'plan'   => Inertia::defer(fn () => PlanResource::make($project->plan->load('items.unit.family'))),
            'family' => $request->user()->familyIn($project)?->only('id', 'name', 'unit_type_id'),
            // A unit's details, shown over the page (?unit=7)
            'unit' => fn () => ($unit = $project->units()->find($request->integer('unit')))
                ? UnitResource::make($unit->load('type', 'family'))
                : null,
        ]);
    }

    /** A unit's details open over the units page: its address leads there */
    public function show(Project $project, Unit $unit): RedirectResponse
    {
        return to_route('units.index', ['unit' => $unit->id]);
    }
}

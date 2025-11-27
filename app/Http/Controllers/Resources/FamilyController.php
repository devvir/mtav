<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateFamilyRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Models\Family;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        defineState('groupMembers', true);

        $families = Family::alphabetically()
            ->withMembers()
            ->with(['members' => fn ($q) => $q->alphabetically()])
            ->when($request->project_id, fn ($q, int $id) => $q->inProject($id))
            ->when($request->q, fn ($q, $search) => $q->search($search, searchMembers: true));

        return inertia('Families/Index', [
            'families' => Inertia::defer(fn () => $families->paginate(30))->deepMerge(),
            'q'        => $request->q ?? '',
        ]);
    }

    public function show(Family $family): Response
    {
        $family->load('project', 'members', 'unitType');

        return inertia('Families/Show', compact('family'));
    }

    public function create(): Response
    {
        $formSpecs = FormService::make(Family::class, FormType::CREATE);

        return inertia('Families/Create', [
            'form' => $formSpecs,
        ]);
    }

    public function store(CreateFamilyRequest $request): RedirectResponse
    {
        $family = Family::create($request->validated());

        return to_route('families.show', $family)
            ->with('success', __('flash.family_created'));
    }

    public function edit(Family $family): Response
    {
        $formSpecs = FormService::make($family, FormType::UPDATE);

        return inertia('Families/Edit', [
            'form' => $formSpecs,
        ]);
    }

    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        $family->update($request->validated());

        return back()
            ->with('success', __('flash.family_updated'));
    }

    public function destroy(Family $family): RedirectResponse
    {
        $family->members()->delete();
        $family->delete();

        return back()->with('success', __('flash.family_deleted'));
    }

    public function restore(Family $family): RedirectResponse
    {
        $family->members()->withTrashed()->restore();
        $family->restore();

        return to_route('families.show', $family)
            ->with('success', __('flash.family_restored'));
    }
}

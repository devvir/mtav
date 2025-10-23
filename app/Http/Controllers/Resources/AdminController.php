<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Admin;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $pool = Project::current()?->admins() ?? Admin::query();

        $admins = $pool->alphabetically()
            ->when($request->q, fn ($query, $q) => $query->search($q));

        return inertia('Admins/Index', [
            'admins' => Inertia::deepMerge(fn () => $admins->paginate(30)),
            'q' => $request->string('q', ''),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin): Response
    {
        $admin->load('projects');

        return inertia('Admins/Show', compact('admin'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $projectsPool = $request->user()->isSuperAdmin()
            ? Project::query()
            : $request->user()->projects();

        return inertia('Admins/Create', [
            'projects' => $projectsPool->alphabetically()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateAdminRequest $request): RedirectResponse
    {
        $admin = Admin::create($request->validated());

        // event(new Registered($admin));

        return to_route('admins.show', $admin->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin): Response
    {
        $admin->load('projects');

        return inertia('Admins/Edit', compact('admin'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminRequest $request, Admin $admin): RedirectResponse
    {
        $admin->update($request->validated());

        return to_route('admins.show', $admin->id);
    }

    /**
     * Delete the resource.
     */
    public function destroy(User $admin): RedirectResponse
    {
        $admin->delete();

        return to_route('admins.index');
    }
}

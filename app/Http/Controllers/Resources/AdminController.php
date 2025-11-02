<?php

namespace App\Http\Controllers\Resources;

use App\Events\UserRegistration;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Admin;
use App\Models\Project;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilteredIndexRequest $request): Response
    {
        $admins = Admin::alphabetically()
            ->when($request->project_id, fn ($q, $projectId) => $q->inProject($projectId))
            ->when($request->q, fn ($q, $search) => $q->search($search));

        return inertia('Admins/Index', [
            'admins' => Inertia::deepMerge(fn () => $admins->paginate(30)),
            'q' => $request->q ?? '',
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
    public function create(): Response
    {
        return inertia('Admins/Create', [
            'projects' => Project::alphabetically()->get(),
        ]);
    }

    /**
     * Create (Invite) a new admin.
     */
    public function store(InvitationService $invitationService, CreateAdminRequest $request): RedirectResponse
    {
        $token = $invitationService->createToken();
        $data = compact('token') + $request->except('project_ids');

        $admin = DB::transaction(fn () => Admin::create($data)->projects()->attach($request->project_ids));

        event(new UserRegistration($admin, $token));

        return to_route('admins.show', $admin->id)
            ->with('success', __('Admin created successfully.'));
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
        $admin->update($request->all());

        return to_route('admins.show', $admin->id)
            ->with('success', __('Admin updated successfully.'));
    }

    /**
     * Delete the resource.
     */
    public function destroy(User $admin): RedirectResponse
    {
        $admin->delete();

        return to_route('admins.index')
            ->with('success', __('Admin deleted successfully.'));
    }

    /**
     * Restore the soft-deleted resource.
     */
    public function restore(Admin $admin): RedirectResponse
    {
        $admin->restore();

        return to_route('admins.show', $admin->id)
            ->with('success', __('Admin restored successfully.'));
    }
}

<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Admin;
use App\Models\Project;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
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

    public function show(Admin $admin): Response
    {
        $admin->load('projects');

        return inertia('Admins/Show', compact('admin'));
    }

    public function create(): Response
    {
        return inertia('Admins/Create', [
            'projects' => Project::alphabetically()->get(),
        ]);
    }

    /**
     * Create (Invite) a new admin.
     */
    public function store(
        CreateAdminRequest $request,
        InvitationService $invitationService
    ): RedirectResponse {
        $admin = $invitationService->inviteAdmin(
            $request->except('project_ids'),
            $request->project_ids
        );

        return to_route('admins.show', $admin)
            ->with('success', __('flash.admin_invitation_sent'));
    }

    public function edit(Admin $admin): Response
    {
        $admin->load('projects');

        return inertia('Admins/Edit', compact('admin'));
    }

    public function update(UpdateAdminRequest $request, Admin $admin): RedirectResponse
    {
        $admin->update($request->validated());

        return to_route('admins.show', $admin)
            ->with('success', __('flash.admin_updated'));
    }

    public function destroy(User $admin): RedirectResponse
    {
        $required = $admin->project?->admins()->count() === 1;
        Gate::denyIf($required, __('validation.project_requires_admin'));

        $admin->delete();

        return to_route('admins.index')
            ->with('success', __('flash.admin_deleted'));
    }

    public function restore(Admin $admin): RedirectResponse
    {
        $admin->restore();

        return to_route('admins.show', $admin)
            ->with('success', __('flash.admin_restored'));
    }
}

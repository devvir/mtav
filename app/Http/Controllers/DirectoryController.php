<?php

namespace App\Http\Controllers;

use App\Http\Resources\DirectoryProjectResource;
use App\Models\Project;
use App\Services\DirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * The projects whose admins list them, for anyone to find: joining at once where a project
 * is open, asking its admins where they decide.
 */
class DirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // What one asked stays in sight, even if the project stopped being listed since
        $projects = Project::query()
            ->where(fn ($query) => $query->inDirectory()->orWhereHas(
                'joinRequests',
                fn ($asked) => $asked->pending()->where('user_id', $user->id),
            ))
            ->when($request->q, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('organization', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->with(['joinRequests' => fn ($asked) => $asked->where('user_id', $user->id)])
            ->withCount('members', 'units')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return inertia('Projects/Directory', [
            'filters' => ['q' => $request->q],
            'listing' => DirectoryProjectResource::collection($projects),
        ]);
    }

    public function join(Request $request, Project $project, DirectoryService $directory): RedirectResponse
    {
        $this->authorize('join', $project);

        $directory->join($project, $request->user());

        return to_route('projects.show', $project)
            ->with('success', __('flash.invitation_accepted', ['project' => $project->name]));
    }

    public function ask(Request $request, Project $project, DirectoryService $directory): RedirectResponse
    {
        $this->authorize('askToJoin', $project);

        $directory->ask($project, $request->user());

        return back()->with('success', __('flash.join_requested', ['project' => $project->name]));
    }

    public function withdraw(Request $request, Project $project): RedirectResponse
    {
        $project->joinRequests()->pending()->where('user_id', $request->user()->id)->delete();

        return back()->with('success', __('flash.join_withdrawn'));
    }
}

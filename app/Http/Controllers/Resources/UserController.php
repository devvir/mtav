<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\ResourceController;
use App\Http\Requests\CreateMemberRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends ResourceController
{
    /**
     * Show the members dashboard.
     */
    public function index(Request $request)
    {
        $groupedByFamily = $request->boolean('grouped', true);

        $families = state('project')->families()
            ->orderBy('name')
            ->with(['members' => fn ($query) => $query->orderBy('lastname')->orderBy('firstname')]);

        $members = state('project')->members()
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->with('family:id,name');

        $resourceBuilder = $groupedByFamily ? $families : $members;

        return inertia('Users/Index', [
            'grouped'  => $groupedByFamily,
            'admins'   => Inertia::optional(fn () => state('project')->admins),
            'resource' => Inertia::deepMerge(fn () => $resourceBuilder->paginate(24)),
        ]);
    }

    /**
     * Show the project details.
     */
    public function show(Request $request, User $user)
    {
        return inertia('Users/Show', [
            'user' => $user,
            'can' => [
                'delete' => can('update', $user),
                'delete' => can('delete', $user),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('Users/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMemberRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        event(new Registered($user));

        return to_route('users.show', $user->id);
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return to_route('users.index');
    }
}

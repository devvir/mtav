<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        updateState('groupMembers', false);

        $pool = Project::current()?->members() ?? User::members();

        $members = $pool->alphabetically()->with('family:id,name')
            ->when($request->q, fn ($query, $q) => $query->search($q, searchFamily: true));

        return inertia('Users/Index', [
            'members' => Inertia::deepMerge(fn () => $members->paginate(20)),
            'q'       => $request->string('q', ''),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        $user->load('family');

        return inertia('Users/Show', compact('user'));
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
    public function store(CreateUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        event(new Registered($user));

        return to_route('users.show', $user->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        return inertia('Users.Edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        User::update($request->validated());

        return to_route('users.show', $user->id);
    }

    /**
     * Delete the resource.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return to_route('users.index');
    }
}

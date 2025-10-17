<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'members' => Inertia::deepMerge(fn () => $members->paginate(30)),
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
    public function create(Request $request): Response
    {
        $familiesPool = Project::current()?->families() ?? Family::query();

        $projectsPool = $request->user()->isSuperAdmin()
            ? Project::query()
            : $request->user()->projects();

        return inertia('Users/Create', [
            'families' => $familiesPool->alphabetically()->get(['id', 'name']),
            'projects' => $projectsPool->alphabetically()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            return User::create([
                ...$request->only(['firstname', 'lastname', 'email']),
                'family_id' => $request->family ?: null,
                'password' => bcrypt(random_bytes(16)),
            ])->joinProject($request->project);
        });

        event(new Registered($user));

        return to_route('users.show', $user->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        $user->load('family', 'projects');

        return inertia('Users/Edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->back();
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

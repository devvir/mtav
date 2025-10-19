<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
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
        $pool = Project::current()?->admins() ?? User::admins();

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
    public function show(User $admin): Response
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
    public function store(CreateUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        // event(new Registered($user));

        return to_route('users.show', $user->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $admin): Response
    {
        $admin->load('projects');

        return inertia('Admins/Edit', compact('admin'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $admin): RedirectResponse
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

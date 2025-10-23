<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

class SetCurrentProjectController
{
    public function set(Request $request, Project $project): RedirectResponse
    {
        if ($request->user()->isNotSuperAdmin() && ! $request->user()->asAdmin()?->manages($project)) {
            throw new UnauthorizedException('You do not have permission to select a Project.');
        }

        updateState('project', $project);

        return back();
    }

    public function unset(): RedirectResponse
    {
        updateState('project', null);

        return redirect()->back();
    }
}

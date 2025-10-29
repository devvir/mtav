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
        if ($request->user()->isNotSuperadmin() && ! $request->user()->asAdmin()?->manages($project)) {
            throw new UnauthorizedException('You do not have permission to select a Project.');
        }

        setState('project', $project);

        return back();
    }

    public function unset(): RedirectResponse
    {
        setState('project', null);

        return redirect()->back();
    }
}

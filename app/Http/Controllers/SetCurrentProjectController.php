<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

class SetCurrentProjectController
{
    public function __invoke(Request $request, Project $project): RedirectResponse
    {
        if ($request->user()->isNotSuperAdmin() && ! $request->user()->manages($project)) {
            throw new UnauthorizedException('You do not have permission to select this Project.');
        }

        setState('project', $project);

        $redirect = $request->get('redirectTo')
            ? redirect()->to($request->get('redirectTo'))
            : redirect()->intended();

        return $redirect->with('success', "Switched to Project `{$project->name}`");
    }
}

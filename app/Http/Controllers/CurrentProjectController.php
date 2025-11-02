<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class CurrentProjectController
{
    public function set(Project $project): RedirectResponse
    {
        setState('project', $project);

        return back();
    }

    public function unset(): RedirectResponse
    {
        setState('project', null);

        return redirect()->back();
    }
}

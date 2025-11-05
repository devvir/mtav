<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class CurrentProjectController
{
    public function set(Project $project): RedirectResponse
    {
        defineState('project', $project);

        return back();
    }

    public function unset(): RedirectResponse
    {
        defineState('project', null);

        return redirect()->back();
    }
}

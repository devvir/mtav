<?php

use App\Models\Project;
use Illuminate\Support\Facades\Context;

/**
 * The project of the current request (resolved from the URL), if any.
 *
 * @see \App\Http\Middleware\ResolveProject
 */
function currentProject(): ?Project
{
    return Context::getHidden('project');
}

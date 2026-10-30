<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\JoinRequest;
use App\Models\Project;
use App\Services\DirectoryService;
use Illuminate\Http\RedirectResponse;

/**
 * Admins answer those asking to join from the directory.
 */
class JoinRequestController extends Controller
{
    public function accept(Project $project, JoinRequest $joinRequest, DirectoryService $directory): RedirectResponse
    {
        abort_unless($joinRequest->isPending(), 404);

        $directory->accept($joinRequest);

        return back()->with('success', __('flash.join_accepted', ['name' => $joinRequest->user->name]));
    }

    public function decline(Project $project, JoinRequest $joinRequest, DirectoryService $directory): RedirectResponse
    {
        abort_unless($joinRequest->isPending(), 404);

        $directory->decline($joinRequest);

        return back()->with('success', __('flash.join_declined', ['name' => $joinRequest->user->name]));
    }
}

<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ArchiveService;
use Illuminate\Http\RedirectResponse;

/**
 * Bringing back what was removed and kept, or, for a family, forgetting it.
 */
class ArchiveController extends Controller
{
    public function restore(Project $project, string $kind, int $id, ArchiveService $archive): RedirectResponse
    {
        $archive->restore($project, $kind, $id);

        return back()->with('success', __('flash.restored'));
    }

    public function destroyFamily(Project $project, int $id, ArchiveService $archive): RedirectResponse
    {
        $archive->deleteFamily($project, $id);

        return back()->with('success', __('flash.family_deleted_for_good'));
    }
}

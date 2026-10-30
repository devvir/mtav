<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ErrorEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Response;

/** What went wrong in the app, latest first, for superadmins; marked solved, it goes */
class ErrorController extends Controller
{
    public function index(): Response
    {
        return inertia('Platform/Errors', [
            'errors' => JsonResource::collection(ErrorEntry::latest('last_seen_at')->paginate(30)),
        ]);
    }

    public function destroy(ErrorEntry $error): RedirectResponse
    {
        $error->delete();

        return back()->with('success', __('flash.error_solved'));
    }
}

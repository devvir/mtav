<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\ResourceController;
use Illuminate\Http\Request;

class GalleryController extends ResourceController
{
    /**
     * Show the current Project's Gallery.
     */
    public function __invoke(Request $request)
    {
        sleep(2); // TODO : remove; just here to test loading spinner

        return inertia('Gallery');
    }
}

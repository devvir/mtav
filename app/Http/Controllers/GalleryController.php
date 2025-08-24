<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GalleryController
{
    /**
     * Show the current Project's Gallery.
     */
    public function __invoke(Request $request)
    {
        return inertia('Gallery');
    }
}

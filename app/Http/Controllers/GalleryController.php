<?php

namespace App\Http\Controllers;

use Inertia\Response;

class GalleryController
{
    /**
     * Show the current Project's Gallery.
     */
    public function __invoke(): Response
    {
        return inertia('Gallery');
    }
}

<?php

namespace App\Http\Controllers\Resources;

class MediaController
{
    public function create()
    {
        return inertia('Media/Create', [
        ]);
    }
}

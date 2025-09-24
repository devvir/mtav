<?php

namespace App\Http\Controllers\Resources;

class EventController
{
    public function create()
    {
        return inertia('Events/Create', [
        ]);
    }
}

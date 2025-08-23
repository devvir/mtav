<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Response;

class ContactAdminController
{
    public function create(User $admin): Response
    {
        return inertia('Admins/Contact', compact('admin'));
    }
}

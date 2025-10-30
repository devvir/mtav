<?php

// Copilot - pending review

namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use Inertia\Response;

class ConfirmMemberAccount extends Controller
{
    public function show(): Response
    {
        return inertia('Auth/ConfirmMemberAccount', [
            'email' => request()->query('email'),
            'token' => request()->query('token'),
        ]);
    }
}

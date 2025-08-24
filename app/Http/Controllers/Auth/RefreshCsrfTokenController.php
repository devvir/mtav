<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

class RefreshCsrfTokenController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        if ($request->user()) {
            $request->session()->regenerateToken();
        }
    }
}

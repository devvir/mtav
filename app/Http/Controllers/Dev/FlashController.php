<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;

class FlashController extends Controller
{
    public function index()
    {
        return inertia('Dev/FlashTester');
    }

    public function send()
    {
        return to_route('dev.flash')->with(request('type'), request('message'));
    }

    public function all()
    {
        return redirect()->route('dev.flash')
            ->with('success', 'Operation completed successfully!')
            ->with('info', 'Here\'s some additional information.')
            ->with('warning', 'Please note this important warning.')
            ->with('error', 'An error occurred during processing.');
    }
}

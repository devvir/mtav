<?php

namespace App\Http\Controllers\Resources;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Show the members dashboard.
     */
    public function index()
    {
        //
    }

    /**
     * Show the project details.
     */
    public function show(Request $request, Unit $unit)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUnitRequest $request): RedirectResponse
    {
        //
    }
}

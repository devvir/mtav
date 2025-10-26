<?php

arch('controllers are in App\Http\Controllers namespace')
    ->expect('App\Http\Controllers')
    ->toBeClasses()
    ->toHaveSuffix('Controller');

arch('controllers use form requests for validation')
    ->expect('App\Http\Controllers\Resources')
    ->toUse('App\Http\Requests');

arch('controllers return proper response types')
    ->expect('App\Http\Controllers')
    ->toUse([
        'Illuminate\Http\RedirectResponse',
        'Inertia\Response',
    ]);

arch('controllers do not use raw DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB')
    ->ignoring('App\Http\Controllers\Resources\MemberController'); // Currently uses DB::transaction

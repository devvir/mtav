<?php

it('does not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

it('keeps models free of HTTP concerns')
    ->expect('App\Models')
    ->not->toUse(['Illuminate\Http\Request', 'Inertia\Inertia']);

it('keeps the lottery solvers free of models')
    ->expect('App\Services\Lottery\Solvers')
    ->not->toUse('App\Models');

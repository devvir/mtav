<?php

arch('policies are in App\Policies namespace')
    ->expect('App\Policies')
    ->toBeClasses()
    ->toHaveSuffix('Policy');

arch('all models have corresponding policies')
    ->expect('App\Models')
    ->ignoring([
        'App\Models\Model',
        'App\Models\User', // User, Admin, Member share policies
    ])
    ->toHavePolicy();

arch('policies do not use direct database queries')
    ->expect('App\Policies')
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
    ]);

<?php

use App\Models\Model;

arch('models extend base Model class')
    ->expect('App\Models')
    ->toExtend(Model::class)
    ->ignoring('App\Models\Model');

arch('models use HasFactory trait')
    ->expect('App\Models')
    ->toUse('Illuminate\Database\Eloquent\Factories\HasFactory')
    ->ignoring('App\Models\Model');

arch('models are in App\Models namespace')
    ->expect('App\Models')
    ->toBeClasses()
    ->toBeFinal(false);

arch('models do not use raw DB facade')
    ->expect('App\Models')
    ->not->toUse('Illuminate\Support\Facades\DB')
    ->ignoring('App\Models\Member'); // Currently uses DB in store method

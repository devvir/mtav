<?php

uses()->group('Arch');

it('does not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

it('does not create JsonResources directly')
    ->expect(Illuminate\Http\Resources\Json\JsonResource::class)
    ->not->toBeUsedIn(['App\\Http\\Controllers\\', 'App\\Models\\']);

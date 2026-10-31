<?php

namespace App\Jobs;

use App\Models\Family;
use App\Services\FamilyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sent ahead to when an invitation expires: nothing else happens at that moment.
 */
class DiscardFamilyIfEmpty implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $familyId)
    {
    }

    public function handle(FamilyService $families): void
    {
        $families->discardIfEmpty(Family::find($this->familyId));
    }
}

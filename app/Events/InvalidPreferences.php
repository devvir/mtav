<?php

namespace App\Events;

use App\Models\Family;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvalidPreferences implements ShouldQueue
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Family $family The family with invalid preferences
     * @param array<int> $unitIds List of invalid unit IDs
     */
    public function __construct(
        public Family $family,
        public array $unitIds
    ) {
    }
}

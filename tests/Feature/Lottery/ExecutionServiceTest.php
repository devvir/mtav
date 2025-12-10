// Copilot - Pending review

<?php

use App\Events\Lottery\LotteryExecutionTriggered;
use App\Models\Event;
use App\Models\Family;
use App\Models\Unit;
use App\Services\Lottery\ExecutionService;
use App\Services\Lottery\Exceptions\CannotExecuteLotteryException;
use App\Services\Lottery\Exceptions\InsufficientFamiliesException;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\Exceptions\UnitFamilyMismatchException;
use Illuminate\Support\Facades\Event as EventFacade;

beforeEach(function () {
    $this->service = app(ExecutionService::class);
});

describe('CannotExecuteLotteryException scenarios', function () {

    test('throws exception when event is not a lottery', function () {
        // Event #2 is a general event, not a lottery
        $event = Event::find(2);

        expect(fn () => $this->service->execute($event))
            ->toThrow(CannotExecuteLotteryException::class);
    });

    test('throws exception when lottery is not published', function () {
        // Event #12 is an unpublished lottery
        $lottery = Event::find(12);

        expect(fn () => $this->service->execute($lottery))
            ->toThrow(CannotExecuteLotteryException::class);
    });

    test('throws exception when lottery is in the future', function () {
        // Event #1 is published but 30 days in the future
        $lottery = Event::find(1);

        expect(fn () => $this->service->execute($lottery))
            ->toThrow(CannotExecuteLotteryException::class);
    });

    test('throws exception when lottery has already been reserved for execution', function () {
        // Event #13 is a past published lottery - mark as executed to test atomicity
        $lottery = Event::find(13);
        $lottery->update(['is_published' => false]); // Simulates already reserved

        expect(fn () => $this->service->execute($lottery))
            ->toThrow(CannotExecuteLotteryException::class);
    });
});

describe('InsufficientFamiliesException scenarios', function () {

    test('throws exception when project has less than 2 families', function () {
        // Project #4 has families #16, #17, #18, #22, #27, #28 - keep only #16
        Family::whereIn('id', [17, 18, 22, 27, 28])->delete();

        $lottery = Event::find(13); // Past lottery for Project #4
        $lottery->update(['is_published' => true]); // Reset if modified by previous tests

        expect(fn () => $this->service->execute($lottery))
            ->toThrow(InsufficientFamiliesException::class);
    });

    test('throws exception when project has 0 families', function () {
        // Event #10 is for Project #3 which has 0 families
        $lottery = Event::find(10);

        expect(fn () => $this->service->execute($lottery))
            ->toThrow(InsufficientFamiliesException::class);
    });
});

describe('LotteryExecutionException scenarios', function () {

    test('throws exception when families already have units assigned', function () {
        // Use Project #4: Unit #13 (Type #7), Family #16 (Type #7)
        $unit = Unit::find(13);
        $family = Family::find(16);

        // Pre-assign unit to family (simulating prior execution or corruption)
        $unit->update(['family_id' => $family->id]);

        $lottery = Event::find(13); // Past lottery for Project #4
        $lottery->update(['is_published' => true]); // Reset if modified

        expect(fn () => $this->service->execute($lottery))
            ->toThrow(LotteryExecutionException::class);

        // Clean up
        $unit->update(['family_id' => null]);
    });
});

describe('UnitFamilyMismatchException scenarios', function () {

    test('throws exception when unit and family counts do not match', function () {
        // Project #1 has mismatched counts:
        // - Type #1: 0 active units, 4 families
        // - Type #2: 1 active unit, 4 families
        // - Type #3: 1 active unit (Unit #2), 4 families
        // Total: 2 active units but 12 families (unbalanced)

        // Use Event #1 but make it past for validation
        $lottery = Event::find(1);
        $lottery->update(['start_date' => now()->subDay(), 'end_date' => now()->subDay()]);

        expect(fn () => $this->service->execute($lottery, []))
            ->toThrow(UnitFamilyMismatchException::class);
    });

    test('does NOT throw exception when override flag is true', function () {
        EventFacade::fake();

        // Same unbalanced Project #1 data, but with override flag
        $lottery = Event::find(1);
        $lottery->update([
            'start_date'   => now()->subDay(),
            'end_date'     => now()->subDay(),
            'is_published' => true,
        ]);

        // Should NOT throw with override flag
        $this->service->execute($lottery, ['mismatch-allowed']);

        // Verify lottery was reserved (is_published set to false)
        expect($lottery->fresh()->is_published)->toBeFalse();

        // Verify event was dispatched
        EventFacade::assertDispatched(LotteryExecutionTriggered::class);
    });
});

describe('successful execution scenarios', function () {

    test('successfully executes lottery with balanced data', function () {
        EventFacade::fake();

        // Event #13 is for Project #4 which is now balanced:
        // - Type #7: 2 units (#13, #14), 2 families (#16, #22)
        // - Type #8: 2 units (#15, #16), 2 families (#17, #27)
        // - Type #9: 2 units (#17, #18), 2 families (#18, #28)
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true]); // Reset if modified

        // Execute lottery
        $this->service->execute($lottery);

        // Verify lottery was reserved (is_published set to false)
        expect($lottery->fresh()->is_published)->toBeFalse();

        // Verify LotteryExecutionTriggered was dispatched
        EventFacade::assertDispatched(LotteryExecutionTriggered::class);
    });

    test('reserves lottery atomically preventing double execution', function () {
        EventFacade::fake();

        // Use Event #13 for Project #4
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true]); // Reset if modified

        // First execution should succeed
        $this->service->execute($lottery);
        expect($lottery->fresh()->is_published)->toBeFalse();

        // Second execution should fail (lottery already reserved)
        expect(fn () => $this->service->execute($lottery->fresh()))
            ->toThrow(CannotExecuteLotteryException::class);
    });
});

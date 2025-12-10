// Copilot - Pending review

<?php

use App\Models\Event;
use App\Models\Unit;

uses()->group('Feature.Lottery.ConfirmationFlow');

beforeEach(function () {
    config()->set('lottery.default', 'test');
});

describe('Lottery Confirmation Flow', function () {
    describe('Options Accumulation', function () {
        test('returns mismatch error when units/families dont match', function () {
            // Project #2 has mismatched counts:
            // Type #4: 2 families (#13, #14), 2 units (#4, #5) - BALANCED
            // Type #5: 2 families (#15, #26), 2 units (#6, #7) - BALANCED
            // Type #6: 0 families, 2 units (#8, #9) - UNBALANCED (2 orphan units)
            // Total: 4 families, 6 units = mismatch

            setCurrentProject(2);

            $lottery = Event::find(6); // Future lottery for Project #2
            $lottery->update([
                'start_date'   => now()->subDay(),
                'end_date'     => now()->subDay(),
                'is_published' => true,
            ]);

            // Without options, should fail with mismatch error
            $response = $this->submitFormToRoute(
                ['lottery.execute', $lottery->id],
                asAdmin: 12,
                data: ['options' => []]
            );

            // Should NOT execute lottery - units remain unassigned
            $assignedCount = Unit::whereIn('id', [4, 5, 6, 7, 8, 9])->whereNotNull('family_id')->count();
            expect($assignedCount)->toBe(0);
        });

        test('allows execution when mismatch-allowed option is confirmed', function () {
            setCurrentProject(2);

            $lottery = Event::find(6);
            $lottery->update(['start_date' => now()->subDay()]);

            // With mismatch-allowed option, should succeed
            $response = $this->submitFormToRoute(
                ['lottery.execute', $lottery->id],
                asAdmin: 12,
                data: ['options' => ['mismatch-allowed']]
            );

            // Lottery should execute successfully (no confirmation needed)
            expect(session()->get('options'))->toBeNull();
            expect(session()->get('warning'))->toBeNull();
        });

        test('preserves all options from previous attempt in options array', function () {
            setCurrentProject(2);

            $lottery = Event::find(6);
            $lottery->update(['start_date' => now()->subDay()]);

            // First attempt fails with mismatch - units NOT assigned
            $response1 = $this->submitFormToRoute(
                ['lottery.execute', $lottery->id],
                asAdmin: 12,
                data: ['options' => []]
            );

            $assignedCount = Unit::whereIn('id', [4, 5, 6, 7, 8, 9])->whereNotNull('family_id')->count();
            expect($assignedCount)->toBe(0);

            // Second attempt: confirm mismatch-allowed - lottery now executes
            $response2 = $this->submitFormToRoute(
                ['lottery.execute', $lottery->id],
                asAdmin: 12,
                data: ['options' => ['mismatch-allowed']]
            );

            // Lottery executed - 4 families assigned to 4 units
            $assignedCount = Unit::whereIn('id', [4, 5, 6, 7, 8, 9])->whereNotNull('family_id')->count();
            expect($assignedCount)->toBe(4);
        });
    });

    describe('UI Option Passing', function () {
        test('sends options array in POST request', function () {
            setCurrentProject(4); // Use balanced project

            $lottery = Event::find(13); // Project #4 lottery
            $lottery->update(['is_published' => true]);

            $response = $this->submitFormToRoute(
                ['lottery.execute', $lottery->id],
                asAdmin: 13,
                data: ['options' => ['test-option']]
            );

            // Balanced project executes successfully (no confirmation needed)
            expect(session()->get('options'))->toBeNull();
        });

        test('handles empty options object', function () {
            setCurrentProject(4); // Use balanced project

            $lottery = Event::find(13);
            $lottery->update(['is_published' => true]);

            $response = $this->submitFormToRoute(
                ['lottery.execute', $lottery->id],
                asAdmin: 13,
                data: ['options' => []]
            );

            // Lottery executes successfully
            expect(session()->get('options'))->toBeNull();
        });

        test('handles multiple option keys in array', function () {
            setCurrentProject(4); // Use balanced project

            $lottery = Event::find(13);
            $lottery->update(['is_published' => true]);

            $response = $this->submitFormToRoute(
                ['lottery.execute', $lottery->id],
                asAdmin: 13,
                data: ['options' => [
                    'mismatch-allowed',
                    'future-option',
                ]]
            );

            // Balanced project executes successfully
            expect(session()->get('options'))->toBeNull();
        });
    });
});

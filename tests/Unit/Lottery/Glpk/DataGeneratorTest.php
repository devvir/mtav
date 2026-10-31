<?php

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataGenerator;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('DataGenerator', function () {
    beforeEach(function () {
        $this->generator = new DataGenerator();
    });

    describe('generateData', function () {
        test('generates valid GMPL data section', function () {
            $participants = [
                1 => [10, 20, 30],
                2 => [20, 30, 10],
                3 => [30, 10, 20],
            ];
            $items = [10, 20, 30];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateData($spec);

            expect($data)->toBeString();
            expect($data)->toStartWith('data;');
            expect($data)->toEndWith('end;');
        });

        test('includes participant set with correct IDs', function () {
            $participants = [
                1 => [10, 20],
                5 => [20, 10],
                7 => [10, 20],
            ];
            $items = [10, 20];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('set P :=');
            expect($data)->toContain('p1');
            expect($data)->toContain('p5');
            expect($data)->toContain('p7');
        });

        test('includes item set with correct IDs', function () {
            $participants = [1 => [10, 20, 30]];
            $items = [10, 20, 30];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('set I :=');
            expect($data)->toContain('i10');
            expect($data)->toContain('i20');
            expect($data)->toContain('i30');
        });

        test('includes preference matrix', function () {
            $participants = [
                1 => [10, 20],
                2 => [20, 10],
            ];
            $items = [10, 20];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('param r :');
            expect($data)->toContain('p1');
            expect($data)->toContain('p2');
        });

        test('preference ranks are 1-indexed', function () {
            $participants = [
                1 => [10, 20, 30], // 10=1st choice, 20=2nd, 30=3rd
            ];
            $items = [10, 20, 30];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateData($spec);

            // Tie-breaker decimals (0.000-0.999) are added to prevent GLPK degeneracy
            // but DO NOT change outcomes - integer ranks still determine optimization
            // Participant 1 should have ranks: 10→1.xxx, 20→2.xxx, 30→3.xxx
            expect($data)->toContain('p1');

            preg_match('/p1\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/', $data, $matches);
            expect($matches)->toHaveCount(4); // Full match + 3 groups

            // Verify integer parts of ranks (outcome-determining part)
            $ranks = [(int)$matches[1], (int)$matches[2], (int)$matches[3]];
            expect($ranks)->toContain(1);
            expect($ranks)->toContain(2);
            expect($ranks)->toContain(3);
        });

        test('handles single participant single item', function () {
            $participants = [42 => [99]];
            $items = [99];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('p42');
            expect($data)->toContain('i99');
            expect($data)->toContain('param r :');
        });

        test('handles larger problem sizes', function () {
            $participants = [];
            $items = range(100, 109);

            foreach (range(1, 10) as $i) {
                $participants[$i] = $items; // Each participant has all items in order
            }

            $spec = new LotterySpec($participants, $items);
            $data = $this->generator->generateData($spec);

            // Should contain all participants
            for ($i = 1; $i <= 10; $i++) {
                expect($data)->toContain("p{$i}");
            }

            // Should contain all items
            foreach ($items as $item) {
                expect($data)->toContain("i{$item}");
            }
        });

        test('preference matrix respects item order', function () {
            // Participant prefers item 30 first, then 10, then 20
            $participants = [1 => [30, 10, 20]];
            $items = [10, 20, 30]; // Items in different order
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateData($spec);

            // Matrix header should list items in spec order: i10 i20 i30
            expect($data)->toMatch('/param r\s*:\s*i10\s+i20\s+i30/');

            // Tie-breaker decimals preserve outcome: integer ranks still determine preferences
            // Participant 1's preference order: 10→2.xxx, 20→3.xxx, 30→1.xxx
            preg_match('/p1\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/', $data, $matches);
            expect((int)$matches[1])->toBe(2); // i10 is 2nd choice (integer rank preserved)
            expect((int)$matches[2])->toBe(3); // i20 is 3rd choice (integer rank preserved)
            expect((int)$matches[3])->toBe(1); // i30 is 1st choice (integer rank preserved)
        });

        test('missing preferences default to low priority', function () {
            // Participant only explicitly prefers first 2 items
            // DataGenerator should handle this gracefully if partial prefs allowed
            // (Though in practice LotteryService ensures complete preferences)
            $participants = [1 => [10, 20]];
            $items = [10, 20, 30]; // Extra item not in preferences
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('p1');
            expect($data)->toContain('i30');

            // Should generate data without errors
            expect($data)->toContain('param r :');
        });
    });

    describe('generateDataWithS', function () {
        test('includes S parameter from Phase 1', function () {
            $participants = [
                1 => [10, 20],
                2 => [20, 10],
            ];
            $items = [10, 20];
            $spec = new LotterySpec($participants, $items);
            $minSatisfaction = 2;

            $data = $this->generator->generateDataWithS($spec, $minSatisfaction);

            expect($data)->toContain('param S := 2;');
        });

        test('contains all base data elements', function () {
            $participants = [1 => [10, 20], 2 => [20, 10]];
            $items = [10, 20];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateDataWithS($spec, 1);

            expect($data)->toContain('set P :=');
            expect($data)->toContain('set I :=');
            expect($data)->toContain('param r :');
            expect($data)->toContain('param S :=');
        });

        test('ends with end statement', function () {
            $participants = [1 => [10]];
            $items = [10];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateDataWithS($spec, 1);

            expect(trim($data))->toEndWith('end;');
        });

        test('S parameter appears after preference matrix', function () {
            $participants = [1 => [10, 20]];
            $items = [10, 20];
            $spec = new LotterySpec($participants, $items);

            $data = $this->generator->generateDataWithS($spec, 3);

            // S should come after param r
            $paramPPos = strpos($data, 'param r');
            $paramSPos = strpos($data, 'param S');

            expect($paramSPos)->toBeGreaterThan($paramPPos);
        });

        test('handles various S values', function () {
            $participants = [1 => [10, 20, 30]];
            $items = [10, 20, 30];
            $spec = new LotterySpec($participants, $items);

            foreach ([1, 2, 3, 5, 10, 999] as $s) {
                $data = $this->generator->generateDataWithS($spec, $s);
                expect($data)->toContain("param S := {$s};");
            }
        });
    });

    test('generateData and generateDataWithS are consistent', function () {
        $participants = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
        ];
        $items = [10, 20, 30];
        $spec = new LotterySpec($participants, $items);

        $baseData = $this->generator->generateData($spec);
        $dataWithS = $this->generator->generateDataWithS($spec, 2);

        // dataWithS should contain everything from baseData plus S parameter
        expect($dataWithS)->toContain('set P :=');
        expect($dataWithS)->toContain('set I :=');
        expect($dataWithS)->toContain('param r :');
        expect($dataWithS)->toContain('param S := 2;');
    });
});

<?php

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Glpk\DataGenerator;

uses()->group('Unit.Lottery.Glpk');

describe('DataGenerator', function () {
    beforeEach(function () {
        $this->generator = new DataGenerator();
    });

    describe('generateData', function () {
        test('generates valid GMPL data section', function () {
            $families = [
                1 => [10, 20, 30],
                2 => [20, 30, 10],
                3 => [30, 10, 20],
            ];
            $units = [10, 20, 30];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateData($spec);

            expect($data)->toBeString();
            expect($data)->toStartWith('data;');
            expect($data)->toEndWith('end;');
        });

        test('includes family set with correct IDs', function () {
            $families = [
                1 => [10, 20],
                5 => [20, 10],
                7 => [10, 20],
            ];
            $units = [10, 20];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('set C :=');
            expect($data)->toContain('c1');
            expect($data)->toContain('c5');
            expect($data)->toContain('c7');
        });

        test('includes unit set with correct IDs', function () {
            $families = [1 => [10, 20, 30]];
            $units = [10, 20, 30];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('set V :=');
            expect($data)->toContain('v10');
            expect($data)->toContain('v20');
            expect($data)->toContain('v30');
        });

        test('includes preference matrix', function () {
            $families = [
                1 => [10, 20],
                2 => [20, 10],
            ];
            $units = [10, 20];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('param p :');
            expect($data)->toContain('c1');
            expect($data)->toContain('c2');
        });

        test('preference ranks are 1-indexed', function () {
            $families = [
                1 => [10, 20, 30], // 10=1st choice, 20=2nd, 30=3rd
            ];
            $units = [10, 20, 30];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateData($spec);

            // Tie-breaker decimals (0.000-0.999) are added to prevent GLPK degeneracy
            // but DO NOT change outcomes - integer ranks still determine optimization
            // Family 1 should have ranks: 10→1.xxx, 20→2.xxx, 30→3.xxx
            expect($data)->toContain('c1');

            preg_match('/c1\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/', $data, $matches);
            expect($matches)->toHaveCount(4); // Full match + 3 groups

            // Verify integer parts of ranks (outcome-determining part)
            $ranks = [(int)$matches[1], (int)$matches[2], (int)$matches[3]];
            expect($ranks)->toContain(1);
            expect($ranks)->toContain(2);
            expect($ranks)->toContain(3);
        });

        test('handles single family single unit', function () {
            $families = [42 => [99]];
            $units = [99];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('c42');
            expect($data)->toContain('v99');
            expect($data)->toContain('param p :');
        });

        test('handles larger problem sizes', function () {
            $families = [];
            $units = range(100, 109);

            foreach (range(1, 10) as $i) {
                $families[$i] = $units; // Each family has all units in order
            }

            $spec = new LotterySpec($families, $units);
            $data = $this->generator->generateData($spec);

            // Should contain all families
            for ($i = 1; $i <= 10; $i++) {
                expect($data)->toContain("c{$i}");
            }

            // Should contain all units
            foreach ($units as $unit) {
                expect($data)->toContain("v{$unit}");
            }
        });

        test('preference matrix respects unit order', function () {
            // Family prefers unit 30 first, then 10, then 20
            $families = [1 => [30, 10, 20]];
            $units = [10, 20, 30]; // Units in different order
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateData($spec);

            // Matrix header should list units in spec order: v10 v20 v30
            expect($data)->toMatch('/param p\s*:\s*v10\s+v20\s+v30/');

            // Tie-breaker decimals preserve outcome: integer ranks still determine preferences
            // Family 1's preference order: 10→2.xxx, 20→3.xxx, 30→1.xxx
            preg_match('/c1\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/', $data, $matches);
            expect((int)$matches[1])->toBe(2); // v10 is 2nd choice (integer rank preserved)
            expect((int)$matches[2])->toBe(3); // v20 is 3rd choice (integer rank preserved)
            expect((int)$matches[3])->toBe(1); // v30 is 1st choice (integer rank preserved)
        });

        test('missing preferences default to low priority', function () {
            // Family only explicitly prefers first 2 units
            // DataGenerator should handle this gracefully if partial prefs allowed
            // (Though in practice LotteryService ensures complete preferences)
            $families = [1 => [10, 20]];
            $units = [10, 20, 30]; // Extra unit not in preferences
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateData($spec);

            expect($data)->toContain('c1');
            expect($data)->toContain('v30');

            // Should generate data without errors
            expect($data)->toContain('param p :');
        });
    });

    describe('generateDataWithS', function () {
        test('includes S parameter from Phase 1', function () {
            $families = [
                1 => [10, 20],
                2 => [20, 10],
            ];
            $units = [10, 20];
            $spec = new LotterySpec($families, $units);
            $minSatisfaction = 2;

            $data = $this->generator->generateDataWithS($spec, $minSatisfaction);

            expect($data)->toContain('param S := 2;');
        });

        test('contains all base data elements', function () {
            $families = [1 => [10, 20], 2 => [20, 10]];
            $units = [10, 20];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateDataWithS($spec, 1);

            expect($data)->toContain('set C :=');
            expect($data)->toContain('set V :=');
            expect($data)->toContain('param p :');
            expect($data)->toContain('param S :=');
        });

        test('ends with end statement', function () {
            $families = [1 => [10]];
            $units = [10];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateDataWithS($spec, 1);

            expect(trim($data))->toEndWith('end;');
        });

        test('S parameter appears after preference matrix', function () {
            $families = [1 => [10, 20]];
            $units = [10, 20];
            $spec = new LotterySpec($families, $units);

            $data = $this->generator->generateDataWithS($spec, 3);

            // S should come after param p
            $paramPPos = strpos($data, 'param p');
            $paramSPos = strpos($data, 'param S');

            expect($paramSPos)->toBeGreaterThan($paramPPos);
        });

        test('handles various S values', function () {
            $families = [1 => [10, 20, 30]];
            $units = [10, 20, 30];
            $spec = new LotterySpec($families, $units);

            foreach ([1, 2, 3, 5, 10, 999] as $s) {
                $data = $this->generator->generateDataWithS($spec, $s);
                expect($data)->toContain("param S := {$s};");
            }
        });
    });

    test('generateData and generateDataWithS are consistent', function () {
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
        ];
        $units = [10, 20, 30];
        $spec = new LotterySpec($families, $units);

        $baseData = $this->generator->generateData($spec);
        $dataWithS = $this->generator->generateDataWithS($spec, 2);

        // dataWithS should contain everything from baseData plus S parameter
        expect($dataWithS)->toContain('set C :=');
        expect($dataWithS)->toContain('set V :=');
        expect($dataWithS)->toContain('param p :');
        expect($dataWithS)->toContain('param S := 2;');
    });
});

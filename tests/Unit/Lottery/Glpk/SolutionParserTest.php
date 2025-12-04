<?php

// Copilot - Pending review

use App\Services\Lottery\Glpk\SolutionParser;

uses()->group('Unit.Lottery.Glpk');

describe('SolutionParser', function () {
    beforeEach(function () {
        $this->parser = new SolutionParser();
        $this->tempDir = sys_get_temp_dir();
    });

    describe('extractObjective', function () {
        test('extracts integer objective value', function () {
            $solContent = <<<'SOL'
Problem:    test
Rows:       5
Columns:    4
Non-zeros:  12
Status:     OPTIMAL
Objective:  resultado = 2 (MINimum)

   No.   Row name   St   Activity     Lower bound   Upper bound    Marginal
------ ------------ -- ------------- ------------- ------------- -------------
     1 resultado    B              2
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $objective = $this->parser->extractObjective($solFile);
                expect($objective)->toBe(2);
            } finally {
                unlink($solFile);
            }
        });

        test('extracts and rounds decimal objective value', function () {
            $solContent = <<<'SOL'
Problem:    test
Rows:       5
Columns:    4
Status:     OPTIMAL
Objective:  resultado = 2.7 (MINimum)
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $objective = $this->parser->extractObjective($solFile);
                expect($objective)->toBe(3); // Rounded
            } finally {
                unlink($solFile);
            }
        });

        test('handles different objective names', function () {
            $solContent = <<<'SOL'
Status:     OPTIMAL
Objective:  myobjective = 5 (MINimum)
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $objective = $this->parser->extractObjective($solFile);
                expect($objective)->toBe(5);
            } finally {
                unlink($solFile);
            }
        });

        test('throws exception when objective not found', function () {
            $solContent = <<<'SOL'
Problem:    test
Rows:       5
Columns:    4
Status:     INFEASIBLE
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                expect(fn () => $this->parser->extractObjective($solFile))
                    ->toThrow(RuntimeException::class, 'Could not extract objective value');
            } finally {
                unlink($solFile);
            }
        });

        test('handles zero objective', function () {
            $solContent = <<<'SOL'
Objective:  resultado = 0 (MINimum)
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $objective = $this->parser->extractObjective($solFile);
                expect($objective)->toBe(0);
            } finally {
                unlink($solFile);
            }
        });

        test('handles large objective values', function () {
            $solContent = <<<'SOL'
Objective:  resultado = 999 (MINimum)
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $objective = $this->parser->extractObjective($solFile);
                expect($objective)->toBe(999);
            } finally {
                unlink($solFile);
            }
        });
    });

    describe('extractAssignments', function () {
        test('extracts simple assignment', function () {
            $solContent = <<<'SOL'
Problem:    test
Status:     OPTIMAL

   No. Column name       Activity     Lower bound   Upper bound
------ ------------    ------------- ------------- -------------
     1 x[c1,v10]    *              1             0             1
     2 x[c1,v20]    *              0             0             1
     3 x[c2,v10]    *              0             0             1
     4 x[c2,v20]    *              1             0             1
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $assignments = $this->parser->extractAssignments($solFile);

                expect($assignments)->toBe([
                    1 => 10,
                    2 => 20,
                ]);
            } finally {
                unlink($solFile);
            }
        });

        test('ignores variables with value 0', function () {
            $solContent = <<<'SOL'
     1 x[c1,v10]    *              1             0             1
     2 x[c1,v20]    *              0             0             1
     3 x[c1,v30]    *              0             0             1
     4 x[c2,v10]    *              0             0             1
     5 x[c2,v20]    *              1             0             1
     6 x[c2,v30]    *              0             0             1
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $assignments = $this->parser->extractAssignments($solFile);

                expect($assignments)->toHaveCount(2);
                expect($assignments)->toBe([1 => 10, 2 => 20]);
            } finally {
                unlink($solFile);
            }
        });

        test('handles multiple families and units', function () {
            $solContent = <<<'SOL'
     1 x[c1,v100]    *              1             0             1
     2 x[c1,v200]    *              0             0             1
     3 x[c5,v100]    *              0             0             1
     4 x[c5,v200]    *              1             0             1
     5 x[c7,v100]    *              0             0             1
     6 x[c7,v300]    *              1             0             1
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $assignments = $this->parser->extractAssignments($solFile);

                expect($assignments)->toBe([
                    1 => 100,
                    5 => 200,
                    7 => 300,
                ]);
            } finally {
                unlink($solFile);
            }
        });

        test('throws exception when no assignments found', function () {
            $solContent = <<<'SOL'
Problem:    test
Status:     OPTIMAL

   No. Column name  St   Activity
------ ------------ -- -------------
     1 x[c1,v10]    *              0
     2 x[c1,v20]    *              0
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                expect(fn () => $this->parser->extractAssignments($solFile))
                    ->toThrow(RuntimeException::class, 'No assignments found');
            } finally {
                unlink($solFile);
            }
        });

        test('handles large problem with many variables', function () {
            $lines = [];
            for ($f = 1; $f <= 10; $f++) {
                for ($u = 100; $u <= 109; $u++) {
                    $value = ($f + $u) % 11 === 0 ? 1 : 0; // Some pattern for assignments
                    $lines[] = sprintf('x[c%d,v%d]    *              %d       0       1', $f, $u, $value);
                }
            }
            $solContent = implode("\n", $lines);

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $assignments = $this->parser->extractAssignments($solFile);

                // Should find assignments where value = 1
                expect($assignments)->toBeArray();
                expect($assignments)->not->toBeEmpty();

                // All values should be unit IDs in valid range
                foreach ($assignments as $familyId => $unitId) {
                    expect($familyId)->toBeInt();
                    expect($unitId)->toBeInt();
                    expect($familyId)->toBeGreaterThanOrEqual(1);
                    expect($familyId)->toBeLessThanOrEqual(10);
                    expect($unitId)->toBeGreaterThanOrEqual(100);
                    expect($unitId)->toBeLessThanOrEqual(109);
                }
            } finally {
                unlink($solFile);
            }
        });

        test('case insensitive matching', function () {
            $solContent = <<<'SOL'
     1 X[C1,V10]    *              1             0             1
     2 X[C2,V20]    *              1             0             1
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $assignments = $this->parser->extractAssignments($solFile);

                expect($assignments)->toBe([
                    1 => 10,
                    2 => 20,
                ]);
            } finally {
                unlink($solFile);
            }
        });

        test('handles fractional activity values near 1', function () {
            // GLPK might output 1.000000 or 0.999999 for binary variables
            $solContent = <<<'SOL'
     1 x[c1,v10]    *              1.000000         0             1
     2 x[c2,v20]    *              0.999999         0             1
     3 x[c3,v30]    *              0.000001         0             1
SOL;

            $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
            file_put_contents($solFile, $solContent);

            try {
                $assignments = $this->parser->extractAssignments($solFile);

                // Should match lines with "1" in activity column (not checking decimals)
                expect($assignments)->toContain(10);
                expect($assignments)->toContain(20);
            } finally {
                unlink($solFile);
            }
        });
    });

    test('parser methods are independent', function () {
        // Test that extractObjective and extractAssignments can work on same file
        $solContent = <<<'SOL'
Problem:    test
Status:     OPTIMAL
Objective:  resultado = 2 (MINimum)

   No. Column name  St   Activity     Lower bound   Upper bound
------ ------------ -- ------------- ------------- -------------
     1 x[c1,v10]    *              1             0             1
     2 x[c2,v20]    *              1             0             1
SOL;

        $solFile = tempnam($this->tempDir, 'test_sol_') . '.sol';
        file_put_contents($solFile, $solContent);

        try {
            $objective = $this->parser->extractObjective($solFile);
            $assignments = $this->parser->extractAssignments($solFile);

            expect($objective)->toBe(2);
            expect($assignments)->toBe([1 => 10, 2 => 20]);
        } finally {
            unlink($solFile);
        }
    });
});

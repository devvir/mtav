<?php

// Copilot - Pending review

use App\Services\Lottery\Solvers\Glpk\ModelGenerator;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('ModelGenerator', function () {
    beforeEach(function () {
        $this->generator = new ModelGenerator();
    });

    describe('generatePhase1Model', function () {
        test('returns valid GMPL model', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toBeString();
            expect($model)->not->toBeEmpty();
        });

        test('contains required sets', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('set C;'); // Families
            expect($model)->toContain('set V;'); // Units
        });

        test('contains required parameters', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('param p{c in C, v in V}'); // Preferences
        });

        test('contains required variables', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('var x{c in C, v in V}, binary'); // Assignment
            expect($model)->toContain('var z, integer'); // Min satisfaction
        });

        test('contains objective function', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('minimize resultado: z;');
        });

        test('contains min satisfaction constraint', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('z_menorIgual{c in C}');
            expect($model)->toContain('z >= sum{v in V} p[c,v] * x[c,v]');
        });

        test('contains family uniqueness constraints', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('unicaAsignacionCoperativista{c in C}');
            expect($model)->toContain('sum{v in V} x[c,v] = 1');
        });

        test('contains unit uniqueness constraints', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('unicaAsignacionCasa{v in V}');
            expect($model)->toContain('sum{c in C} x[c,v] = 1');
        });

        test('ends with end statement', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toEndWith('end;');
        });
    });

    describe('generatePhase2Model', function () {
        test('returns valid GMPL model', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toBeString();
            expect($model)->not->toBeEmpty();
        });

        test('contains required sets', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('set C;');
            expect($model)->toContain('set V;');
        });

        test('contains required parameters including S', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('param p{c in C, v in V}');
            expect($model)->toContain('param S;'); // Min satisfaction from Phase 1
        });

        test('contains assignment variable', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('var x{c in C, v in V}, binary');
        });

        test('contains objective to minimize sum of ranks', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('minimize resultado:');
            expect($model)->toContain('sum{c in C, v in V} p[c,v] * x[c,v]');
        });

        test('contains min satisfaction constraint', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('satisfaccionMinima{c in C}');
            expect($model)->toContain('sum{v in V} p[c,v] * x[c,v] <= S');
        });

        test('contains family uniqueness constraints', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('unicaAsignacionCoperativista{c in C}');
            expect($model)->toContain('sum{v in V} x[c,v] = 1');
        });

        test('contains unit uniqueness constraints', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('unicaAsignacionCasa{v in V}');
            expect($model)->toContain('sum{c in C} x[c,v] = 1');
        });

        test('ends with end statement', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toEndWith('end;');
        });
    });

    test('Phase 1 and Phase 2 models are different', function () {
        $phase1 = $this->generator->generatePhase1Model();
        $phase2 = $this->generator->generatePhase2Model();

        expect($phase1)->not->toBe($phase2);

        // Phase 1 has z variable, Phase 2 has S parameter
        expect($phase1)->toContain('var z, integer');
        expect($phase2)->toContain('param S;');
    });

    test('models use Spanish constraint names', function () {
        $phase1 = $this->generator->generatePhase1Model();
        $phase2 = $this->generator->generatePhase2Model();

        // Verify Spanish naming from original Windows application
        expect($phase1)->toContain('Cooperativistas');
        expect($phase1)->toContain('Viviendas');
        expect($phase1)->toContain('Prioridad');

        expect($phase2)->toContain('Cooperativistas');
        expect($phase2)->toContain('Viviendas');
        expect($phase2)->toContain('Prioridad');
    });
});

<?php


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

            expect($model)->toContain('set P;'); // Participants
            expect($model)->toContain('set I;'); // Items
        });

        test('contains required parameters', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('param r{p in P, i in I}'); // Preferences
        });

        test('contains required variables', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('var x{p in P, i in I}, binary'); // Assignment
            expect($model)->toContain('var z, integer'); // Min satisfaction
        });

        test('contains objective function', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('minimize resultado: z;');
        });

        test('contains min satisfaction constraint', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('z_menorIgual{p in P}');
            expect($model)->toContain('z >= sum{i in I} r[p,i] * x[p,i]');
        });

        test('contains participant uniqueness constraints', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('oneItemPerParticipant{p in P}');
            expect($model)->toContain('sum{i in I} x[p,i] = 1');
        });

        test('contains item uniqueness constraints', function () {
            $model = $this->generator->generatePhase1Model();

            expect($model)->toContain('oneParticipantPerItem{i in I}');
            expect($model)->toContain('sum{p in P} x[p,i] = 1');
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

            expect($model)->toContain('set P;');
            expect($model)->toContain('set I;');
        });

        test('contains required parameters including S', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('param r{p in P, i in I}');
            expect($model)->toContain('param S;'); // Min satisfaction from Phase 1
        });

        test('contains assignment variable', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('var x{p in P, i in I}, binary');
        });

        test('contains objective to minimize sum of ranks', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('minimize resultado:');
            expect($model)->toContain('sum{p in P, i in I} r[p,i] * x[p,i]');
        });

        test('contains min satisfaction constraint', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('satisfaccionMinima{p in P}');
            expect($model)->toContain('sum{i in I} r[p,i] * x[p,i] <= S');
        });

        test('contains participant uniqueness constraints', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('oneItemPerParticipant{p in P}');
            expect($model)->toContain('sum{i in I} x[p,i] = 1');
        });

        test('contains item uniqueness constraints', function () {
            $model = $this->generator->generatePhase2Model();

            expect($model)->toContain('oneParticipantPerItem{i in I}');
            expect($model)->toContain('sum{p in P} x[p,i] = 1');
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

    test('models describe their sets and rank', function () {
        $phase1 = $this->generator->generatePhase1Model();
        $phase2 = $this->generator->generatePhase2Model();

        expect($phase1)->toContain('Participants');
        expect($phase1)->toContain('Items');
        expect($phase1)->toContain('Rank');

        expect($phase2)->toContain('Participants');
        expect($phase2)->toContain('Items');
        expect($phase2)->toContain('Rank');
    });
});

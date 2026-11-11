<?php

use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkException;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkTimeoutException;
use App\Services\Lottery\Solvers\Glpk\lib\Glpsol;

uses()->group('Unit.Lottery.Glpk');

/**
 * Running glpsol: within GLPK's own time limit, and ours past it should GLPK's fail to stop it.
 */

/** A stand-in for glpsol that runs the given shell lines, whatever it is asked */
function fakeGlpsol(string $script): Glpsol
{
    $path = test()->dir . '/glpsol';
    file_put_contents($path, "#!/bin/sh\n{$script}\n");
    chmod($path, 0755);

    return new Glpsol($path);
}

beforeEach(function () {
    $this->dir = sys_get_temp_dir() . '/' . uniqid('fake-glpsol-');
    mkdir($this->dir);
});

afterEach(function () {
    array_map(unlink(...), glob("{$this->dir}/*"));
    rmdir($this->dir);
});

it('stops a glpsol that outlives GLPK\'s own limit', function () {
    $glpsol = fakeGlpsol('sleep 10');
    $started = microtime(true);

    expect(fn () => $glpsol->solve('m.mod', 'd.dat', 's.sol', 0))->toThrow(GlpkTimeoutException::class, 'failsafe');
    expect(microtime(true) - $started)->toBeLessThan(5);
});

it('says GLPK stopped at its own limit when it says so', function () {
    $glpsol = fakeGlpsol('echo "TIME LIMIT EXCEEDED; SEARCH TERMINATED"');

    expect(fn () => $glpsol->solve('m.mod', 'd.dat', 's.sol', 5))->toThrow(GlpkTimeoutException::class, '--tmlim');
});

it('fails with what glpsol said when it fails', function () {
    $glpsol = fakeGlpsol('echo "Model file not found"; exit 1');

    expect(fn () => $glpsol->solve('m.mod', 'd.dat', 's.sol', 5))
        ->toThrow(GlpkException::class, 'GLPK execution failed: Model file not found');
});

it('passes GLPK its limit in whole seconds, and the files it works on', function () {
    $glpsol = fakeGlpsol("echo \"\$@\" > {$this->dir}/args");

    $glpsol->solve('m.mod', 'd.dat', 's.sol', 7);

    expect(trim(file_get_contents("{$this->dir}/args")))->toBe('--scale --model m.mod --data d.dat --tmlim 7 --output s.sol');
});

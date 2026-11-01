<?php

/**
 * What the release is built with cannot change under it: third-party actions by commit, base
 * images by digest, downloads by checksum.
 */

it('runs third-party actions by exact commit', function () {
    foreach (glob(base_path('.github/workflows/*.yml')) as $workflow) {
        preg_match_all('/uses:\s+([^\s#]+)/', file_get_contents($workflow), $used);

        foreach (array_filter($used[1], fn ($action) => ! str_starts_with($action, './')) as $action) {
            expect($action)->toMatch('/@[0-9a-f]{40}$/');
        }
    }
});

it('builds the release from base images by exact digest', function () {
    $dockerfile = file_get_contents(base_path('deploy/Dockerfile'));

    preg_match_all('/^\s*FROM\s+(\S+)/m', $dockerfile, $from);
    preg_match_all('/--from=(\S+:\S+)/', $dockerfile, $copied);

    expect($from[1])->not->toBeEmpty()->and($copied[1])->not->toBeEmpty();
    foreach ([...$from[1], ...$copied[1]] as $image) {
        expect($image)->toMatch('/@sha256:[0-9a-f]{64}$/');
    }
});

it('checks what the release build downloads', function () {
    $dockerfile = file_get_contents(base_path('deploy/Dockerfile'));

    expect($dockerfile)->toContain('sha256sum -c -')
        ->not->toMatch('/wget[^\n]*\|\s*tar/');
});

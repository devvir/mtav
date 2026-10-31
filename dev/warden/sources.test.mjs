import assert from 'node:assert/strict';
import { test } from 'node:test';
import { jsonLines, releasesOf, stacksOf } from './sources.mjs';

test('reads one object a line', () => {
  assert.deepEqual(jsonLines('{"a":1}\n\n{"a":2}\n'), [{ a: 1 }, { a: 2 }]);
});

test('groups MTAV containers by stack, and tells who is unwell', () => {
  const stacks = stacksOf([
    { Names: 'mtav-dev-php-1', State: 'running', Status: 'Up 3 hours (healthy)', Labels: 'com.docker.compose.project=mtav-dev,x=y' },
    { Names: 'mtav-dev-mysql-1', State: 'running', Status: 'Up 1 minute (unhealthy)', Labels: 'com.docker.compose.project=mtav-dev' },
    { Names: 'mtav-dev-worker-1', State: 'exited', Status: 'Exited (1)', Labels: 'com.docker.compose.project=mtav-dev' },
    { Names: 'jh-other', State: 'running', Status: 'Up', Labels: 'com.docker.compose.project=jh-dev' },
  ]);

  assert.deepEqual(Object.keys(stacks), ['mtav-dev']);
  assert.deepEqual(stacks['mtav-dev'].map((c) => c.healthy), [true, false, false]);
});

test('reads what each slot of the server runs', () => {
  const releases = releasesOf(`
mtav-staging-green-app-1 ghcr.io/devvir/mtav:sha-ffd2ed5c4b5a9fa7
mtav-staging-blue-app-1 ghcr.io/devvir/mtav:sha-ad71718d5413fc35
mtav-staging-switch-1 nginxinc/nginx-unprivileged:1.30
mtav-production-blue-app-1 ghcr.io/devvir/mtav:sha-497ce33e9e6f60f0
`);

  assert.deepEqual(releases.staging, [{ slot: 'green', commit: 'ffd2ed5c' }, { slot: 'blue', commit: 'ad71718d' }]);
  assert.equal(releases.production[0].commit, '497ce33e');
});

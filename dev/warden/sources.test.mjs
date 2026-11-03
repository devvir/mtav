/* global process */
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { jsonLines, releasesOf, serversOf, sources, stacksOf, usageOf } from './sources.mjs';

test('reads one object a line', () => {
  assert.deepEqual(jsonLines('{"a":1}\n\n{"a":2}\n'), [{ a: 1 }, { a: 2 }]);
});

test('groups MTAV containers by stack, and tells who is unwell', () => {
  const stacks = stacksOf([
    {
      Names: 'mtav-dev-php-1',
      State: 'running',
      Status: 'Up 3 hours (healthy)',
      Labels: 'com.docker.compose.project=mtav-dev,x=y',
    },
    {
      Names: 'mtav-dev-mysql-1',
      State: 'running',
      Status: 'Up 1 minute (unhealthy)',
      Labels: 'com.docker.compose.project=mtav-dev',
    },
    {
      Names: 'mtav-dev-worker-1',
      State: 'exited',
      Status: 'Exited (1)',
      Labels: 'com.docker.compose.project=mtav-dev',
    },
    {
      Names: 'jh-other',
      State: 'running',
      Status: 'Up',
      Labels: 'com.docker.compose.project=jh-dev',
    },
  ]);

  assert.deepEqual(Object.keys(stacks), ['mtav-dev']);
  assert.deepEqual(
    stacks['mtav-dev'].map((c) => c.healthy),
    [true, false, false],
  );
});

test('reads what each slot of the server runs, and which one is live', () => {
  const releases = releasesOf(`
mtav-staging-green-app-1 ghcr.io/devvir/mtav:sha-ffd2ed5c4b5a9fa7
mtav-staging-blue-app-1 ghcr.io/devvir/mtav:sha-ad71718d5413fc35
mtav-staging-switch-1 nginxinc/nginx-unprivileged:1.30
mtav-production-blue-app-1 ghcr.io/devvir/mtav:sha-497ce33e9e6f60f0
live staging blue
live production blue
`);

  assert.deepEqual(releases.staging, [
    { slot: 'green', commit: 'ffd2ed5c', live: false },
    { slot: 'blue', commit: 'ad71718d', live: true },
  ]);
  assert.equal(releases.production[0].commit, '497ce33e');
});

test('says the server is not set up without MTAV_SERVERS, instead of failing', async () => {
  const before = process.env.MTAV_SERVERS;
  delete process.env.MTAV_SERVERS;
  try {
    assert.deepEqual(await sources('.').server.read(), { notSetUp: true });
  } finally {
    if (before !== undefined) process.env.MTAV_SERVERS = before;
  }
});

test("reads each container's CPU and memory", () => {
  assert.deepEqual(
    usageOf([{ Name: 'mtav-dev-php-1', CPUPerc: '12.50%', MemUsage: '148.4MiB / 31.06GiB' }]),
    {
      'mtav-dev-php-1': { cpu: 12.5, memory: '148.4MiB' },
    },
  );
});

test('reads the servers to watch, comma separated', () => {
  assert.deepEqual(serversOf(' x@one, y@two ,,'), ['x@one', 'y@two']);
  assert.deepEqual(serversOf(undefined), []);
});

/* global process */
import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
  jsonLines,
  latestBackups,
  releasesOf,
  serverFigures,
  serversOf,
  sources,
  stacksOf,
  usageOf,
} from './sources.mjs';

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

test('reads what an AWS server says of itself', () => {
  const figures = serverFigures(
    [
      'cores 2',
      'load 0.08 0.16 0.22',
      'memory 1924116480 577376256',
      'disk 19682557952 12458430464',
      'data 4143677440 3561578496',
      `live dev blue ghcr.io/devvir/mtav:sha-${'a'.repeat(40)}`,
      `previous dev green ghcr.io/devvir/mtav:sha-${'b'.repeat(40)}`,
      'container mtav-dev-blue-app-1 running Up 2 hours (healthy)',
      'sessions 0',
      'certificate Feb  3 05:00:00 2027 GMT',
      'backup ',
    ].join('\n'),
  );
  assert.equal(figures.cores, 2);
  assert.deepEqual(figures.load, [0.08, 0.16, 0.22]);
  assert.deepEqual(figures.memory, { total: 1924116480, free: 577376256 });
  assert.deepEqual(figures.data, { total: 4143677440, free: 3561578496 });
  assert.deepEqual(figures.live.dev, { slot: 'blue', commit: 'a'.repeat(40) });
  assert.deepEqual(figures.previous.dev, { slot: 'green', commit: 'b'.repeat(40) });
  assert.deepEqual(figures.containers, [
    { name: 'mtav-dev-blue-app-1', state: 'running', status: 'Up 2 hours (healthy)' },
  ]);
  assert.equal(figures.sessions, 0);
  assert.equal(figures.certificate, 'Feb  3 05:00:00 2027 GMT');
  assert.equal(figures.backup, null);
});

test("keeps each environment's newest backup, with its files", () => {
  const latest = latestBackups([
    { Key: 'preview/dev/2026-11-04/database.sql.gz', Size: 10, LastModified: 'a' },
    { Key: 'preview/dev/2026-11-05/database.sql.gz', Size: 20, LastModified: 'b' },
    { Key: 'preview/dev/2026-11-05/files.tar.gz', Size: 5, LastModified: 'c' },
    { Key: 'preview/staging/2026-11-05/database.sql.gz', Size: 7, LastModified: 'd' },
    { Key: 'stray', Size: 1, LastModified: 'e' },
  ]);
  assert.deepEqual(latest.dev, {
    server: 'preview',
    day: '2026-11-05',
    at: 'b',
    files: { database: 20, files: 5 },
  });
  assert.deepEqual(Object.keys(latest).sort(), ['dev', 'staging']);
  assert.deepEqual(latestBackups(undefined), {});
});

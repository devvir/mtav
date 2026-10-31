// What the warden watches. Each source reads one thing, on its own pace, and either gives what it
// found or fails on its own: one that is down never hides the others.
import { execFile } from 'node:child_process';
import os from 'node:os';

const SITES = { dev: 'https://dev.mtav.uy', staging: 'https://stg.mtav.uy', production: 'https://www.mtav.uy' };
const SERVER = process.env.MTAV_SERVER ?? 'x@179.27.99.138';
const ACCOUNTS_VOLUME = 'claude-accounts';

const run = (command, args, { timeout = 20_000, cwd } = {}) =>
  new Promise((resolve, reject) => {
    execFile(command, args, { timeout, cwd, maxBuffer: 8 * 1024 * 1024 }, (error, stdout, stderr) =>
      error ? reject(new Error(String(stderr || error.message).trim().slice(0, 300))) : resolve(stdout),
    );
  });

/** docker's `--format json`: one object a line */
export const jsonLines = (text) =>
  text
    .split('\n')
    .filter((line) => line.trim())
    .map((line) => JSON.parse(line));

/** Containers by the stack they belong to, each with whether it is well */
export function stacksOf(containers) {
  const stacks = {};
  for (const container of containers) {
    const project = /com\.docker\.compose\.project=([^,]+)/.exec(container.Labels ?? '')?.[1] ?? 'other';
    if (!project.startsWith('mtav')) continue;
    (stacks[project] ??= []).push({
      name: container.Names,
      state: container.State,
      status: container.Status,
      healthy: container.State === 'running' && !/unhealthy|starting/.test(container.Status),
    });
  }
  return stacks;
}

/** What each app slot of the server runs: `name image` lines */
export function releasesOf(text) {
  const releases = {};
  for (const line of text.split('\n')) {
    const match = /^mtav-(\w+)-(blue|green)-app-1 .*sha-([0-9a-f]{8})/.exec(line.trim());
    if (match) (releases[match[1]] ??= []).push({ slot: match[2], commit: match[3] });
  }
  return releases;
}

export const sources = (repo) => ({
  host: {
    every: 5,
    read: async () => {
      const disk = (await run('df', ['-k', '--output=size,avail', repo])).trim().split('\n').pop().trim().split(/\s+/);
      return {
        load: os.loadavg().map((value) => Math.round(value * 10) / 10),
        cores: os.cpus().length,
        memory: { total: os.totalmem(), free: os.freemem() },
        disk: { total: Number(disk[0]) * 1024, free: Number(disk[1]) * 1024 },
      };
    },
  },

  stacks: {
    every: 10,
    read: async () => stacksOf(jsonLines(await run('docker', ['ps', '-a', '--filter', 'name=mtav-', '--format', 'json']))),
  },

  repository: {
    every: 30,
    read: async () => {
      const git = (...args) => run('git', args, { cwd: repo });
      const heads = await git('for-each-ref', '--format=%(refname:short) %(objectname:short=8) %(committerdate:relative)', 'refs/remotes/origin');
      return {
        branch: (await git('branch', '--show-current')).trim(),
        commit: (await git('log', '-1', '--format=%h %s')).trim(),
        changed: (await git('status', '--short')).split('\n').filter(Boolean).length,
        remote: heads.split('\n').filter(Boolean).map((line) => {
          const [name, commit, ...when] = line.split(' ');
          return { name: name.replace('origin/', ''), commit, when: when.join(' ') };
        }),
      };
    },
  },

  pipelines: {
    every: 60,
    read: async () =>
      JSON.parse(
        await run('gh', ['run', 'list', '--limit', '10', '--json', 'workflowName,headBranch,headSha,status,conclusion,createdAt,url'], { cwd: repo }),
      ).map((item) => ({
        workflow: item.workflowName,
        branch: item.headBranch,
        commit: item.headSha.slice(0, 8),
        status: item.status,
        conclusion: item.conclusion,
        at: item.createdAt,
        url: item.url,
      })),
  },

  sites: {
    every: 60,
    read: async () => {
      const check = async ([name, url]) => {
        const started = Date.now();
        try {
          const response = await fetch(`${url}/up`, { signal: AbortSignal.timeout(10_000), redirect: 'manual' });
          return { name, url, status: response.status, ms: Date.now() - started };
        } catch (error) {
          return { name, url, status: null, error: String(error.message).slice(0, 120) };
        }
      };
      return Promise.all(Object.entries(SITES).map(check));
    },
  },

  server: {
    every: 60,
    read: async () => {
      const text = await run('ssh', ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=8', SERVER, "docker ps --format '{{.Names}} {{.Image}}'; echo ---; cut -d' ' -f1-3 /proc/loadavg; free -b | sed -n 2p; df -B1 --output=size,avail / | tail -1"], { timeout: 25_000 });
      const [containers, stats = ''] = text.split('---\n');
      const [load, memory, disk] = stats.trim().split('\n');
      const [, memoryTotal, , , , , memoryFree] = (memory ?? '').trim().split(/\s+/);
      const [diskTotal, diskFree] = (disk ?? '').trim().split(/\s+/);
      return {
        releases: releasesOf(containers),
        load: (load ?? '').split(' ').map(Number),
        memory: { total: Number(memoryTotal), free: Number(memoryFree) },
        disk: { total: Number(diskTotal), free: Number(diskFree) },
      };
    },
  },

  app: {
    every: 60,
    read: async () => {
      const code = `echo json_encode(collect(['projects', 'users', 'families', 'units', 'memberships', 'messages', 'conversations', 'jobs', 'failed_jobs'])->mapWithKeys(fn ($t) => [$t => Illuminate\\Support\\Facades\\Schema::hasTable($t) ? DB::table($t)->count() : null]));`;
      return JSON.parse((await run('docker', ['exec', 'mtav-dev-php-1', 'php', 'artisan', 'tinker', '--execute', code], { timeout: 30_000 })).trim().split('\n').pop());
    },
  },

  // The readings every service on these accounts already shares: asking again would only spend
  // the plans' patience. It never touches a login.
  claude: {
    every: 120,
    read: async () => {
      const text = await run('docker', ['run', '--rm', '-v', `${ACCOUNTS_VOLUME}:/a:ro`, 'alpine', 'sh', '-c', 'for f in /a/*/.usage.json; do [ -f "$f" ] && echo "$(basename "$(dirname "$f")") $(cat "$f")"; done']);
      return text
        .split('\n')
        .filter(Boolean)
        .map((line) => {
          const space = line.indexOf(' ');
          return { account: line.slice(0, space), ...JSON.parse(line.slice(space + 1)) };
        });
    },
  },
});

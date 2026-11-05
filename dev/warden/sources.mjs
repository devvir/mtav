/* global process, fetch, AbortSignal, URL */
// What the warden watches. Each source reads one thing, on its own pace, and either gives what it
// found or fails on its own: one that is down never hides the others.
import { execFile } from 'node:child_process';
import dns from 'node:dns/promises';
import os from 'node:os';
import { aws, onServer, servers } from './aws.mjs';

const SITES = {
  dev: 'https://dev.mtav.uy',
  staging: 'https://stg.mtav.uy',
  production: 'https://www.mtav.uy',
  sorteo: 'https://sorteo.mtav.uy',
};
const ACCOUNTS_VOLUME = 'claude-accounts';
/** The server MTAV ran on before AWS: a name still leading there has not moved */
const BEFORE_AWS = '179.27.99.138';

const run = (command, args, { timeout = 20_000, cwd } = {}) =>
  new Promise((resolve, reject) => {
    execFile(
      command,
      args,
      { timeout, cwd, maxBuffer: 8 * 1024 * 1024 },
      (error, stdout, stderr) =>
        error
          ? reject(
              new Error(
                String(stderr || error.message)
                  .trim()
                  .slice(0, 300),
              ),
            )
          : resolve(stdout),
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
    const project =
      /com\.docker\.compose\.project=([^,]+)/.exec(container.Labels ?? '')?.[1] ?? 'other';
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

/**
 * What each app slot of the server runs (`name image` lines), and which one is live (`live
 * <environment> <slot>` lines)
 */
export function releasesOf(text) {
  const releases = {};
  const live = {};
  for (const line of text.split('\n')) {
    const said = /^live (\w+) (blue|green)/.exec(line.trim());
    if (said) live[said[1]] = said[2];
  }
  for (const line of text.split('\n')) {
    const match = /^mtav-(\w+)-(blue|green)-app-1 .*sha-([0-9a-f]{8})/.exec(line.trim());
    if (match) {
      (releases[match[1]] ??= []).push({
        slot: match[2],
        commit: match[3],
        live: live[match[1]] === match[2],
      });
    }
  }
  return releases;
}

/** docker stats' figures by container name */
export function usageOf(stats) {
  return Object.fromEntries(
    stats.map((row) => [
      row.Name,
      { cpu: parseFloat(row.CPUPerc), memory: row.MemUsage.split(' / ')[0] },
    ]),
  );
}

/** MTAV_SERVERS: user@host, comma separated */
export const serversOf = (setting) =>
  String(setting ?? '')
    .split(',')
    .map((host) => host.trim())
    .filter(Boolean);

/** One server's releases and figures, over ssh */
async function readServer(host) {
  const text = await run(
    'ssh',
    [
      '-o',
      'BatchMode=yes',
      '-o',
      'ConnectTimeout=8',
      host,
      "docker ps --format '{{.Names}} {{.Image}}'; for f in /mtav/*/live; do echo \"live $(basename $(dirname $f)) $(cut -d' ' -f1 $f)\"; done; echo ---; nproc; cut -d' ' -f1-3 /proc/loadavg; free -b | sed -n 2p; df -B1 --output=size,avail / | tail -1",
    ],
    { timeout: 25_000 },
  );
  const [containers, stats = ''] = text.split('---\n');
  const [cores, load, memory, disk] = stats.trim().split('\n');
  const [, memoryTotal, , , , , memoryFree] = (memory ?? '').trim().split(/\s+/);
  const [diskTotal, diskFree] = (disk ?? '').trim().split(/\s+/);
  return {
    releases: releasesOf(containers),
    figures: {
      cores: Number(cores) || 1,
      load: (load ?? '').split(' ').map(Number),
      memory: { total: Number(memoryTotal), free: Number(memoryFree) },
      disk: { total: Number(diskTotal), free: Number(diskFree) },
    },
  };
}

/** What one AWS server says of itself (the lines `cloudScript` prints), as figures */
export function serverFigures(text) {
  const figures = { live: {}, previous: {}, containers: [] };
  for (const line of text.split('\n')) {
    const [what, ...rest] = line.trim().split(' ');
    if (what === 'cores') figures.cores = Number(rest[0]) || 1;
    if (what === 'load') figures.load = rest.map(Number);
    if (what === 'memory') figures.memory = { total: Number(rest[0]), free: Number(rest[1]) };
    if (what === 'disk') figures.disk = { total: Number(rest[0]), free: Number(rest[1]) };
    if (what === 'data') figures.data = { total: Number(rest[0]), free: Number(rest[1]) };
    if (what === 'sessions') figures.sessions = Number(rest[0]);
    if (what === 'certificate') figures.certificate = rest.join(' ') || null;
    if (what === 'backup') figures.backup = rest.join(' ') || null;
    if (what === 'live' || what === 'previous') {
      const [environment, slot, image] = rest;
      figures[what][environment] = {
        slot,
        commit: /sha-([0-9a-f]{40})/.exec(image ?? '')?.[1] ?? null,
      };
    }
    if (what === 'container') {
      const [name, state, ...status] = rest;
      figures.containers.push({ name, state, status: status.join(' ') });
    }
  }
  return figures;
}

/** What `serverFigures` reads, printed on the server in one go */
const cloudScript = [
  'echo "cores $(nproc)"',
  'echo "load $(cut -d\' \' -f1-3 /proc/loadavg)"',
  'free -b | awk \'/Mem:/{print "memory", $2, $7}\'',
  'df -B1 --output=size,avail / | tail -1 | awk \'{print "disk", $1, $2}\'',
  'df -B1 --output=size,avail /srv/mtav-data 2>/dev/null | tail -1 | awk \'{print "data", $1, $2}\'',
  'for f in /mtav/*/live /mtav/*/previous; do [ -f "$f" ] && echo "$(basename $f) $(basename $(dirname $f)) $(cat $f)"; done',
  "docker ps -a --format 'container {{.Names}} {{.State}} {{.Status}}'",
  'echo "sessions $(ls /run/systemd/sessions 2>/dev/null | grep -vc ref)"',
  'echo "certificate $(openssl x509 -enddate -noout -in /etc/letsencrypt/live/mtav/cert.pem 2>/dev/null | cut -d= -f2)"',
  'echo "backup $(systemctl show mtav-backups.service -p ExecMainExitTimestamp --value) $(systemctl show mtav-backups.service -p Result --value)"',
];

/** The newest backup of each environment, from the bucket's listing (`<server>/<environment>/<day>/<what>`) */
export function latestBackups(objects) {
  const latest = {};
  for (const { Key: key, Size: size, LastModified: at } of objects ?? []) {
    const [server, environment, day, what] = key.split('/');
    if (!what) continue;
    const known = latest[environment];
    if (!known || day > known.day) latest[environment] = { server, day, at, files: {} };
    if (latest[environment].day === day) latest[environment].files[what.split('.')[0]] = size;
  }
  return latest;
}

export const sources = (repo) => ({
  host: {
    every: 5,
    read: async () => {
      const disk = (await run('df', ['-k', '--output=size,avail', repo]))
        .trim()
        .split('\n')
        .pop()
        .trim()
        .split(/\s+/);
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
    read: async () => {
      const stacks = stacksOf(
        jsonLines(await run('docker', ['ps', '-a', '--filter', 'name=mtav-', '--format', 'json'])),
      );
      const usage = usageOf(
        jsonLines(await run('docker', ['stats', '--no-stream', '--format', 'json'])),
      );
      for (const containers of Object.values(stacks)) {
        for (const container of containers) Object.assign(container, usage[container.name] ?? {});
      }
      return stacks;
    },
  },

  repository: {
    every: 30,
    read: async () => {
      const git = (...args) => run('git', args, { cwd: repo });
      const heads = await git(
        'for-each-ref',
        '--format=%(refname:short) %(objectname:short=8) %(committerdate:unix) %(committerdate:relative)',
        'refs/remotes/origin',
      );
      // Branches main already has: done with, unless kept on purpose
      const merged = new Set(
        (await git('branch', '-r', '--merged', 'origin/main'))
          .split('\n')
          .map((line) => line.trim().replace('origin/', ''))
          .filter(Boolean),
      );
      return {
        branch: (await git('branch', '--show-current')).trim(),
        commit: (await git('log', '-1', '--format=%h %s')).trim(),
        changed: (await git('status', '--short')).split('\n').filter(Boolean).length,
        remote: heads
          .split('\n')
          .filter(Boolean)
          .map((line) => {
            const [name, commit, at, ...when] = line.split(' ');
            const short = name.replace('origin/', '');
            return {
              name: short,
              commit,
              at: Number(at) * 1000,
              when: when.join(' '),
              merged: merged.has(short),
            };
          })
          .filter((branch) => branch.name !== 'origin' && branch.name !== 'HEAD'),
      };
    },
  },

  pipelines: {
    every: 60,
    read: async () =>
      JSON.parse(
        await run(
          'gh',
          [
            'run',
            'list',
            '--limit',
            '30',
            '--json',
            'workflowName,headBranch,headSha,status,conclusion,createdAt,url',
          ],
          { cwd: repo },
        ),
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
        // Where its name leads now: the server before AWS, or AWS
        const leads = await dns
          .resolve4(new URL(url).hostname)
          .then((addresses) => (addresses.includes(BEFORE_AWS) ? 'before' : 'aws'))
          .catch(() => null);
        const started = Date.now();
        try {
          const response = await fetch(`${url}/up`, {
            signal: AbortSignal.timeout(10_000),
            redirect: 'manual',
          });
          return { name, url, leads, status: response.status, ms: Date.now() - started };
        } catch (error) {
          return { name, url, leads, status: null, error: String(error.message).slice(0, 120) };
        }
      };
      return Promise.all(Object.entries(SITES).map(check));
    },
  },

  server: {
    every: 60,
    read: async () => {
      const hosts = serversOf(process.env.MTAV_SERVERS);
      if (!hosts.length) return { notSetUp: true };

      // Each on its own: one that does not answer says so, the others still do
      const answers = await Promise.allSettled(hosts.map(readServer));
      return {
        servers: answers.map((answer, index) =>
          answer.status === 'fulfilled'
            ? { host: hosts[index], ...answer.value.figures }
            : { host: hosts[index], error: answer.reason.message },
        ),
        releases: Object.assign(
          {},
          ...answers
            .filter((answer) => answer.status === 'fulfilled')
            .map((answer) => answer.value.releases),
        ),
      };
    },
  },

  app: {
    every: 60,
    read: async () => {
      const code = `echo json_encode(collect(['projects', 'users', 'families', 'units', 'memberships', 'messages', 'conversations', 'jobs', 'failed_jobs'])->mapWithKeys(fn ($t) => [$t => Illuminate\\Support\\Facades\\Schema::hasTable($t) ? DB::table($t)->count() : null]));`;
      return JSON.parse(
        (
          await run(
            'docker',
            ['exec', 'mtav-dev-php-1', 'php', 'artisan', 'tinker', '--execute', code],
            { timeout: 30_000 },
          )
        )
          .trim()
          .split('\n')
          .pop(),
      );
    },
  },

  // The readings every service on these accounts already shares: asking again would only spend
  // the plans' patience. It never touches a login.
  claude: {
    every: 120,
    read: async () => {
      const text = await run('docker', [
        'run',
        '--rm',
        '-v',
        `${ACCOUNTS_VOLUME}:/a:ro`,
        'alpine',
        'sh',
        '-c',
        'for f in /a/*/.usage.json; do [ -f "$f" ] && echo "$(basename "$(dirname "$f")") $(cat "$f")"; done',
      ]);
      return text
        .split('\n')
        .filter(Boolean)
        .map((line) => {
          const space = line.indexOf(' ');
          return { account: line.slice(0, space), ...JSON.parse(line.slice(space + 1)) };
        });
    },
  },

  // MTAV's servers on AWS: each as AWS has it, and, while running, what it says of itself (one Systems Manager
  // command each, only while someone looks)
  cloud: {
    every: 60,
    read: async () => {
      const found = await servers();
      return Promise.all(
        found.map(async (server) => {
          if (server.state !== 'running') return server;
          try {
            return {
              ...server,
              ...serverFigures(await onServer(server.id, cloudScript, { timeout: 40_000 })),
            };
          } catch (error) {
            return { ...server, error: error.message };
          }
        }),
      );
    },
  },

  // What it costs: the free plan's credits left, and this month's spend against the budget (both free to ask;
  // Cost Explorer would charge each question)
  money: {
    every: 900,
    read: async () => {
      const [plan, budget] = await Promise.allSettled([
        aws(['freetier', 'get-account-plan-state'], { region: 'us-east-1' }),
        aws(
          [
            'budgets',
            'describe-budget',
            '--account-id',
            '690260957652',
            '--budget-name',
            'mtav-monthly',
          ],
          { region: 'us-east-1' },
        ),
      ]);
      const spend = budget.value?.Budget;
      return {
        plan: plan.value
          ? {
              type: plan.value.accountPlanType,
              credits: plan.value.accountPlanRemainingCredits?.amount ?? null,
              until: plan.value.accountPlanExpirationDate,
            }
          : null,
        month: spend
          ? {
              spent: Number(spend.CalculatedSpend?.ActualSpend?.Amount ?? 0),
              forecast: Number(spend.CalculatedSpend?.ForecastedSpend?.Amount ?? 0) || null,
              limit: Number(spend.BudgetLimit.Amount),
            }
          : null,
      };
    },
  },

  backups: {
    every: 900,
    read: async () =>
      latestBackups(
        (await aws(['s3api', 'list-objects-v2', '--bucket', 'mtav-backups-690260957652'])).Contents,
      ),
  },
});

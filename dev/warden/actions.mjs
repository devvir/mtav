// What the warden may do when asked, each as a job (jobs.mjs) whose output the page follows. Deploys go through the
// same GitHub workflows as ever (the warden starts one and follows it to its end): nothing here deploys around them.
// Servers start and stop, the infrastructure is checked (OpenTofu's plan) and that very plan applied, a backup is
// taken now, a container's log read: through AWS, as the `mtav` profile.
import path from 'node:path';
import { PROFILE, REGION, onServer, servers } from './aws.mjs';
import { pause } from './jobs.mjs';

const ENVIRONMENTS = ['dev', 'staging', 'production', 'sorteo'];
const PLAN = 'warden.tfplan';

const isEnvironment = (value) => ENVIRONMENTS.includes(value);
const isRef = (value) => typeof value === 'string' && /^[\w./-]{1,100}$/.test(value);
const isCommit = (value) => typeof value === 'string' && /^[0-9a-f]{7,40}$/.test(value);
const isContainer = (value) => typeof value === 'string' && /^mtav-[a-z0-9-]{1,60}$/.test(value);

/** Starts a workflow, then follows its run until it ends, saying each job and step as they finish */
const workflow = (repo, file, ref, fields) => async (job, runOne) => {
  const before = Date.now();
  const args = [
    'workflow',
    'run',
    file,
    '--ref',
    ref,
    ...Object.entries(fields).flatMap(([key, value]) => ['-f', `${key}=${value}`]),
  ];
  if ((await runOne('gh', args, { cwd: repo })) !== 0) return 1;

  // Its run, once GitHub lists it
  let run = null;
  for (let tries = 0; tries < 20 && !run; tries++) {
    await pause(3_000);
    const listed = await gh(repo, [
      'run',
      'list',
      '--workflow',
      file,
      '--limit',
      '5',
      '--json',
      'databaseId,createdAt,url,status',
    ]);
    run = listed.find((one) => Date.parse(one.createdAt) >= before - 5_000) ?? null;
  }
  if (!run) {
    job.say('GitHub did not list the run: see the Actions tab');
    return 1;
  }
  job.say(`Following ${run.url}`);

  const said = new Set();
  for (;;) {
    await pause(8_000);
    const view = await gh(repo, [
      'run',
      'view',
      String(run.databaseId),
      '--json',
      'status,conclusion,jobs',
    ]);
    for (const one of view.jobs) {
      for (const step of one.steps ?? []) {
        const key = `${one.name}/${step.name}`;
        if (step.status === 'completed' && !said.has(key)) {
          said.add(key);
          job.say(
            `${step.conclusion === 'success' ? '✓' : step.conclusion === 'skipped' ? '–' : '✗'} ${one.name}: ${step.name}`,
          );
        }
      }
    }
    if (view.status === 'completed') {
      job.say(`The run ended: ${view.conclusion}`);
      if (view.conclusion !== 'success') {
        await runOne('gh', ['run', 'view', String(run.databaseId), '--log-failed'], { cwd: repo });
      }
      return view.conclusion === 'success' ? 0 : 1;
    }
  }
};

const gh = async (repo, args) => {
  const { execFile } = await import('node:child_process');
  return new Promise((resolve, reject) =>
    execFile('gh', args, { cwd: repo, timeout: 30_000 }, (error, stdout, stderr) =>
      error
        ? reject(new Error(String(stderr || error.message).trim()))
        : resolve(JSON.parse(stdout)),
    ),
  );
};

const serverOf = async (environment) => {
  const found = (await servers()).find((one) => one.environments.includes(environment));
  if (!found) throw new Error(`no server holds ${environment}`);
  return found;
};

const tofu = (repo) => ({ cwd: path.join(repo, 'infra/aws/main'), env: { AWS_PROFILE: PROFILE } });

/** Every action: whether its parameters are right, its title, and its steps */
export const actions = (repo) => ({
  deploy: {
    check: ({ environment, ref, commit }) =>
      isEnvironment(environment) &&
      (environment !== 'dev' || isRef(ref)) &&
      (commit === undefined || commit === '' || isCommit(commit)),
    title: ({ environment, ref }) =>
      `Deploy ${environment}${environment === 'dev' ? ` from ${ref}` : ''}`,
    steps: ({ environment, ref, commit }) => {
      if (environment === 'dev') return [workflow(repo, 'dev.yml', ref, {})];
      if (environment === 'staging') {
        return [
          (job) => {
            job.say(
              'Staging runs what the staging branch holds: merge into it and push, and the pipeline deploys it.',
            );
            return 1;
          },
        ];
      }
      return [
        workflow(repo, 'production.yml', 'main', { environment, ...(commit ? { commit } : {}) }),
      ];
    },
  },

  rollback: {
    check: ({ environment }) => isEnvironment(environment),
    title: ({ environment }) => `Roll ${environment} back`,
    steps: ({ environment }) => [workflow(repo, 'rollback.yml', 'main', { environment })],
  },

  fresh: {
    check: ({ environment }) => environment === 'dev' || environment === 'staging',
    title: ({ environment }) => `Start ${environment} over, with demo data`,
    steps: ({ environment }) => [workflow(repo, 'fresh.yml', 'main', { environment })],
  },

  start: {
    check: ({ server }) => typeof server === 'string' && /^i-[0-9a-f]{8,17}$/.test(server),
    title: () => 'Start a server',
    steps: ({ server }) => [
      [
        'aws',
        [
          'ec2',
          'start-instances',
          '--instance-ids',
          server,
          '--profile',
          PROFILE,
          '--region',
          REGION,
          '--output',
          'text',
        ],
      ],
      [
        'aws',
        [
          'ec2',
          'wait',
          'instance-running',
          '--instance-ids',
          server,
          '--profile',
          PROFILE,
          '--region',
          REGION,
        ],
      ],
      (job) => {
        job.say('Running. Its environments come up by themselves within a minute.');
        return 0;
      },
    ],
  },

  stop: {
    check: ({ server }) => typeof server === 'string' && /^i-[0-9a-f]{8,17}$/.test(server),
    title: () => 'Stop a server',
    steps: ({ server }) => [
      [
        'aws',
        [
          'ec2',
          'stop-instances',
          '--instance-ids',
          server,
          '--profile',
          PROFILE,
          '--region',
          REGION,
          '--output',
          'text',
        ],
      ],
      [
        'aws',
        [
          'ec2',
          'wait',
          'instance-stopped',
          '--instance-ids',
          server,
          '--profile',
          PROFILE,
          '--region',
          REGION,
        ],
      ],
      (job) => {
        job.say('Stopped: only its disks and its fixed address cost while it is.');
        return 0;
      },
    ],
  },

  check: {
    check: () => true,
    title: () => 'Check the infrastructure',
    steps: () => [['tofu', ['plan', '-input=false', '-no-color', `-out=${PLAN}`], tofu(repo)]],
  },

  apply: {
    check: () => true,
    title: () => 'Apply the check',
    // The plan the check made and someone read, never a new one
    steps: () => [['tofu', ['apply', '-input=false', '-no-color', PLAN], tofu(repo)]],
  },

  backup: {
    check: ({ environment }) => isEnvironment(environment),
    title: ({ environment }) => `Back up ${environment}'s server now`,
    steps: ({ environment }) => [
      async (job) => {
        const server = await serverOf(environment);
        job.say(`On ${server.name}: every environment's database and files, to S3`);
        job.say(
          await onServer(
            server.id,
            [
              'systemctl start mtav-backups.service',
              'journalctl -u mtav-backups.service -n 20 --no-pager -o cat',
            ],
            { timeout: 900_000 },
          ),
        );
        return 0;
      },
    ],
  },

  logs: {
    check: ({ environment, container }) => isEnvironment(environment) && isContainer(container),
    title: ({ container }) => `The log of ${container}`,
    steps: ({ environment, container }) => [
      async (job) => {
        const server = await serverOf(environment);
        job.say(
          await onServer(server.id, [`docker logs --tail 300 --timestamps ${container} 2>&1`]),
        );
        return 0;
      },
    ],
  },
});

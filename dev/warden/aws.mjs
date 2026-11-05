/* global process, setTimeout */
// AWS, as the warden reaches it: the `mtav` profile (MTAV's own project, its long-lived key), in Ohio. A command on a
// server goes through Systems Manager (no SSH anywhere), waited for, its output given back.
import { execFile } from 'node:child_process';

export const PROFILE = process.env.MTAV_AWS_PROFILE ?? 'mtav';
export const REGION = 'us-east-2';

export const aws = (args, { timeout = 30_000, region = REGION } = {}) =>
  new Promise((resolve, reject) => {
    execFile(
      'aws',
      [...args, '--profile', PROFILE, '--region', region, '--output', 'json'],
      { timeout, maxBuffer: 16 * 1024 * 1024 },
      (error, stdout, stderr) =>
        error
          ? reject(
              new Error(
                String(stderr || error.message)
                  .trim()
                  .slice(0, 400),
              ),
            )
          : resolve(stdout ? JSON.parse(stdout) : null),
    );
  });

/** MTAV's servers as AWS has them: each with its state and the environments it holds */
export async function servers() {
  const answer = await aws([
    'ec2',
    'describe-instances',
    '--filters',
    'Name=tag:app,Values=mtav',
    'Name=instance-state-name,Values=pending,running,stopping,stopped',
  ]);
  return answer.Reservations.flatMap((reservation) => reservation.Instances).map((instance) => {
    const tag = (key) => instance.Tags?.find((one) => one.Key === key)?.Value ?? '';
    return {
      id: instance.InstanceId,
      name: tag('server'),
      environments: tag('environments').split(',').filter(Boolean),
      state: instance.State.Name,
      type: instance.InstanceType,
      address: instance.PublicIpAddress ?? null,
      since: instance.LaunchTime,
    };
  });
}

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/** Shell lines run on a server, as root, through Systems Manager; resolves with what they printed */
export async function onServer(instance, lines, { timeout = 120_000 } = {}) {
  const sent = await aws([
    'ssm',
    'send-command',
    '--instance-ids',
    instance,
    '--document-name',
    'AWS-RunShellScript',
    '--parameters',
    JSON.stringify({ commands: lines }),
  ]);
  const id = sent.Command.CommandId;
  const until = Date.now() + timeout;
  while (Date.now() < until) {
    await wait(2_000);
    let answer;
    try {
      answer = await aws([
        'ssm',
        'get-command-invocation',
        '--command-id',
        id,
        '--instance-id',
        instance,
      ]);
    } catch {
      continue; // not registered yet
    }
    if (['Pending', 'InProgress', 'Delayed'].includes(answer.Status)) continue;
    if (answer.Status !== 'Success') {
      throw new Error((answer.StandardErrorContent || answer.Status).trim().slice(0, 400));
    }
    return answer.StandardOutputContent;
  }
  throw new Error('the server did not answer in time');
}

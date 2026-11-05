/* global setTimeout */
// What the warden is doing for someone: each action a job, its output kept line by line as it comes, so the page
// follows it live and finds it again after a reload. The last few are kept; one action of a kind runs at a time.
import { spawn } from 'node:child_process';

const KEPT = 20;
const LINES = 4000;

const jobs = new Map();
let counter = 0;

/** A job's public face: what the page reads */
const face = (job, from = 0) => ({
  id: job.id,
  kind: job.kind,
  title: job.title,
  state: job.state,
  started: job.started,
  ended: job.ended,
  lines: job.lines.slice(from),
  total: job.lines.length,
  // Its outcome in one line, where its tool says one (OpenTofu's plan or apply)
  summary:
    job.lines.findLast((line) => /^(Plan:|No changes|Apply complete!|Error:)/.test(line.trim())) ??
    null,
});

export const listJobs = () =>
  [...jobs.values()].reverse().map((job) => face(job, job.lines.length));

export const readJob = (id, from = 0) => {
  const job = jobs.get(id);
  return job ? face(job, from) : null;
};

/**
 * Runs a list of steps (each `[command, args, { cwd, env }]`, or a function given the job to say things and return
 * steps of its own) one after another; the first that fails ends the job. Refused while one of the same kind runs.
 */
export function startJob(kind, title, steps) {
  if ([...jobs.values()].some((job) => job.kind === kind && job.state === 'running')) {
    throw new Error('the same is already running');
  }
  const job = {
    id: String(++counter),
    kind,
    title,
    state: 'running',
    started: Date.now(),
    ended: null,
    lines: [],
  };
  jobs.set(job.id, job);
  while (jobs.size > KEPT) jobs.delete(jobs.keys().next().value);

  const say = (text) => {
    for (const line of String(text).split('\n')) {
      job.lines.push(line);
      if (job.lines.length > LINES) job.lines.splice(0, job.lines.length - LINES);
    }
  };
  job.say = say;

  const runOne = (command, args, { cwd, env } = {}) =>
    new Promise((resolve) => {
      say(`$ ${[command, ...args].join(' ')}`);
      const child = spawn(command, args, { cwd, env: { ...globalThis.process.env, ...env } });
      let partial = '';
      const take = (chunk) => {
        partial += chunk.toString();
        const lines = partial.split(/\r?\n/);
        partial = lines.pop();
        lines.forEach(say);
      };
      child.stdout.on('data', take);
      child.stderr.on('data', take);
      child.on('error', (error) => {
        say(error.message);
        resolve(1);
      });
      child.on('close', (code) => {
        if (partial) say(partial);
        resolve(code ?? 1);
      });
    });

  (async () => {
    try {
      for (const step of steps) {
        const code = typeof step === 'function' ? await step(job, runOne) : await runOne(...step);
        if (code !== 0) {
          job.state = 'failed';
          return;
        }
      }
      job.state = 'done';
    } catch (error) {
      say(error.message);
      job.state = 'failed';
    } finally {
      job.ended = Date.now();
    }
  })();

  return job.id;
}

/** A pause inside a job's step */
export const pause = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

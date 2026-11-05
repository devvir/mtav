/* global process, setInterval, console, Buffer, URL */
// The MTAV warden: a page, on a developer's machine, watching everything from above and doing what deploying and
// running MTAV takes: the local stacks, the machine, the sites, AWS's servers and what each environment runs, the
// pipelines, the money, the backups, the Claude accounts, the app's figures. It is not part of the app and never runs
// from a deployment: it needs this machine's docker, gh, aws and tofu.
//
//   ./mtav warden         then http://localhost:9190
import { readFile } from 'node:fs/promises';
import { createServer } from 'node:http';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { actions } from './actions.mjs';
import { listJobs, readJob, startJob } from './jobs.mjs';
import { sources } from './sources.mjs';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const REPO = path.resolve(HERE, '../..');
const PAGE = path.join(HERE, 'dist');
const PORT = Number(process.env.MTAV_WARDEN_PORT ?? 9190);
/** With nobody looking, it reads nothing */
const IDLE_AFTER_MS = 60_000;

const watched = sources(REPO);
const doable = actions(REPO);
const readings = Object.fromEntries(
  Object.keys(watched).map((name) => [name, { at: null, data: null, error: null, reading: false }]),
);
let lastLook = 0;

async function refresh(name) {
  const reading = readings[name];
  if (reading.reading) return;
  reading.reading = true;
  try {
    reading.data = await watched[name].read();
    reading.error = null;
  } catch (error) {
    reading.error = error.message;
  }
  reading.at = Date.now();
  reading.reading = false;
}

setInterval(() => {
  if (Date.now() - lastLook > IDLE_AFTER_MS) return;
  for (const [name, source] of Object.entries(watched)) {
    if (!readings[name].at || Date.now() - readings[name].at >= source.every * 1000)
      void refresh(name);
  }
}, 1000);

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript',
  '.css': 'text/css',
  '.woff2': 'font/woff2',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
};

const json = (response, status, body) => {
  response.writeHead(status, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' });
  response.end(JSON.stringify(body));
};

const bodyOf = (request) =>
  new Promise((resolve) => {
    const chunks = [];
    request.on('data', (chunk) => chunks.push(chunk));
    request.on('end', () => {
      try {
        resolve(JSON.parse(Buffer.concat(chunks).toString() || '{}'));
      } catch {
        resolve(null);
      }
    });
  });

const local = (value) => ['localhost', '127.0.0.1'].includes(String(value ?? '').split(':')[0]);

const server = createServer(async (request, response) => {
  // For this machine alone, whatever asks: nothing here is for another page or address
  if (!local(request.headers.host)) {
    response.writeHead(403).end();
    return;
  }
  const url = new URL(request.url ?? '/', 'http://localhost');

  if (url.pathname === '/api') {
    lastLook = Date.now();
    json(response, 200, { readings, jobs: listJobs() });
    return;
  }

  // Doing something: only from the warden's own page (its header, which another site's page cannot send here
  // without asking first, and this server never says yes)
  const action = /^\/api\/actions\/([a-z]+)$/.exec(url.pathname)?.[1];
  if (action && request.method === 'POST') {
    if (
      request.headers['x-warden'] !== '1' ||
      (request.headers.origin && !local(new URL(request.headers.origin).host))
    ) {
      json(response, 403, { error: 'only the warden itself' });
      return;
    }
    const params = await bodyOf(request);
    const known = doable[action];
    if (!known || !params || !known.check(params)) {
      json(response, 400, { error: 'not something the warden does' });
      return;
    }
    try {
      json(response, 200, { id: startJob(action, known.title(params), known.steps(params)) });
    } catch (error) {
      json(response, 409, { error: error.message });
    }
    return;
  }

  const job = /^\/api\/jobs\/(\d+)$/.exec(url.pathname)?.[1];
  if (job) {
    lastLook = Date.now();
    const found = readJob(job, Number(url.searchParams.get('from') ?? 0));
    if (found) json(response, 200, found);
    else json(response, 404, { error: 'no such job' });
    return;
  }

  // The page: its built files, and index.html for any other address
  const file = path.join(PAGE, path.normalize(url.pathname).replace(/^(\.\.[/\\])+/, ''));
  for (const candidate of [file, path.join(PAGE, 'index.html')]) {
    if (!candidate.startsWith(PAGE)) break;
    try {
      const content = await readFile(candidate);
      if (candidate.endsWith('index.html')) lastLook = Date.now();
      response.writeHead(200, {
        'Content-Type': TYPES[path.extname(candidate)] ?? 'application/octet-stream',
        'Cache-Control': candidate.includes(`${path.sep}assets${path.sep}`)
          ? 'public, max-age=31536000, immutable'
          : 'no-store',
      });
      response.end(content);
      return;
    } catch {
      // not this one
    }
  }
  response.writeHead(404).end('The page is not built: ./mtav warden builds it.');
});

server.listen(PORT, '127.0.0.1', () => console.log(`MTAV warden: http://localhost:${PORT}`));

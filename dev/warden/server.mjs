// The MTAV warden: a page, on a developer's machine, watching everything from above: the local
// stacks, the machine, deployments, pipelines, the Claude accounts, the app's figures. It is not
// part of the app and never runs from a deployment: it needs this machine's docker, gh and ssh.
//
//   ./mtav warden         then http://localhost:9190
import { readFile } from 'node:fs/promises';
import { createServer } from 'node:http';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { sources } from './sources.mjs';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const PORT = Number(process.env.MTAV_WARDEN_PORT ?? 9190);
/** With nobody looking, it reads nothing */
const IDLE_AFTER_MS = 60_000;

const watched = sources(path.resolve(HERE, '../..'));
const readings = Object.fromEntries(Object.keys(watched).map((name) => [name, { at: null, data: null, error: null, reading: false }]));
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
    if (!readings[name].at || Date.now() - readings[name].at >= source.every * 1000) void refresh(name);
  }
}, 1000);

const server = createServer(async (request, response) => {
  // For this machine alone, whatever asks: nothing here is for another page or address
  if (request.headers.host?.split(':')[0] !== 'localhost' && request.headers.host?.split(':')[0] !== '127.0.0.1') {
    response.writeHead(403).end();
    return;
  }

  if (request.url === '/api') {
    lastLook = Date.now();
    response.writeHead(200, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' });
    response.end(JSON.stringify(readings));
    return;
  }

  if (request.url === '/' || request.url === '') {
    lastLook = Date.now();
    response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' });
    response.end(await readFile(path.join(HERE, 'page.html')));
    return;
  }

  response.writeHead(404).end();
});

server.listen(PORT, '127.0.0.1', () => console.log(`MTAV warden: http://localhost:${PORT}`));

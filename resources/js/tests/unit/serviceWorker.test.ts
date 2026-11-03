import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it, vi } from 'vitest';

/** The worker as the browser runs it: its handlers, over a fake cache and network */
function worker(network: (request: Request) => Promise<Response>) {
  const handlers: Record<string, (event: unknown) => void> = {};
  const told: boolean[] = [];
  const kept = new Map<string, Response>();
  const cache = {
    match: async (request: Request) => kept.get(request.url)?.clone(),
    put: async (request: Request, response: Response) => void kept.set(request.url, response),
    keys: async () => [...kept.keys()].map((url) => new Request(url)),
  };
  const self = {
    addEventListener: (type: string, handler: (event: unknown) => void) =>
      (handlers[type] = handler),
    clients: {
      matchAll: async () => [
        { postMessage: (message: { online: boolean }) => told.push(message.online) },
      ],
    },
  };
  const code = readFileSync(resolve(__dirname, '../../../../public/sw.js'), 'utf8');
  new Function('self', 'caches', 'fetch', 'location', code)(
    self,
    { open: async () => cache, delete: async () => true },
    network,
    { origin: 'https://example.test' },
  );

  /** A visit within the app, as Inertia makes it */
  const visit = async (path: string) => {
    let answer: Promise<Response> | undefined;
    handlers.fetch!({
      request: new Request(`https://example.test${path}`, { headers: { 'X-Inertia': 'true' } }),
      respondWith: (response: Promise<Response>) => (answer = response),
    });
    return answer!;
  };

  return { visit, told };
}

const page = (body: string) => new Response(body, { status: 200 });

describe('the offline worker', () => {
  it('waits for a slow server rather than answering with what it kept, and never says offline', async () => {
    vi.useFakeTimers();
    let replies = 0;
    const { visit, told } = worker(async () => {
      replies++;
      if (replies === 1) return page('kept');
      await new Promise((resolve) => setTimeout(resolve, 20_000));
      return page('fresh');
    });
    await (await visit('/projects')).text();

    const answer = visit('/projects');
    await vi.advanceTimersByTimeAsync(20_000);

    expect(await (await answer).text()).toBe('fresh');
    expect(told).not.toContain(false);
    vi.useRealTimers();
  });

  it('answers with what it kept when the server cannot be reached, and says so', async () => {
    let reachable = true;
    const { visit, told } = worker(async () => {
      if (!reachable) throw new TypeError('Failed to fetch');
      return page('kept');
    });
    await (await visit('/projects')).text();
    reachable = false;

    expect(await (await visit('/projects')).text()).toBe('kept');
    expect(told.at(-1)).toBe(false);
  });
});

import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it, vi } from 'vitest';

const ORIGIN = 'https://example.test';

/** The worker as the browser runs it: its handlers, over fake caches and network */
function worker(network: (request: Request) => Promise<Response>) {
  const handlers: Record<string, (event: unknown) => void> = {};
  const told: boolean[] = [];
  const stores = new Map<string, Map<string, Response>>();
  const store = (name: string) => {
    if (!stores.has(name)) stores.set(name, new Map());
    return stores.get(name)!;
  };
  const cacheOf = (name: string) => ({
    match: async (request: Request) => store(name).get(request.url)?.clone(),
    put: async (request: Request, response: Response) =>
      void store(name).set(request.url, response),
    delete: async (request: Request) => store(name).delete(request.url),
    keys: async () => [...store(name).keys()].map((url) => new Request(url)),
  });
  const self = {
    addEventListener: (type: string, handler: (event: unknown) => void) =>
      (handlers[type] = handler),
    clients: {
      claim: async () => undefined,
      matchAll: async () => [
        { postMessage: (message: { online: boolean }) => told.push(message.online) },
      ],
    },
  };
  const code = readFileSync(resolve(__dirname, '../../../../public/sw.js'), 'utf8');
  new Function('self', 'caches', 'fetch', 'location', code)(
    self,
    {
      open: async (name: string) => cacheOf(name),
      delete: async (name: string) => stores.delete(name),
    },
    // As a worker does, an address of its own is taken from its origin
    (request: Request | string) =>
      network(typeof request === 'string' ? new Request(`${ORIGIN}${request}`) : request),
    { origin: ORIGIN },
  );

  /** What the worker was given to finish, beyond its answer */
  let pending: Promise<unknown>[] = [];
  const settled = async () => {
    while (pending.length) {
      const now = pending;
      pending = [];
      await Promise.all(now);
    }
  };
  const fire = (type: string, event: object) => {
    handlers[type]!({ ...event, waitUntil: (promise: Promise<unknown>) => pending.push(promise) });
    return settled();
  };

  /** A request through the worker: a visit within the app, as Inertia makes it, or a file */
  const ask = async (path: string, headers: Record<string, string> = { 'X-Inertia': 'true' }) => {
    let answer: Promise<Response> | undefined;
    handlers.fetch!({
      request: new Request(`${ORIGIN}${path}`, { headers }),
      respondWith: (response: Promise<Response>) => (answer = response),
      waitUntil: (promise: Promise<unknown>) => pending.push(promise),
    });
    const response = await answer!;
    await settled();
    return response;
  };
  const visit = (path: string) => ask(path);
  const file = (path: string) => ask(path, {});

  /** The addresses kept in each cache */
  const kept = (name: string) => [...store(name).keys()].map((url) => url.replace(ORIGIN, ''));

  /** Something already kept, as an earlier worker left it */
  const seed = (name: string, path: string) => void store(name).set(`${ORIGIN}${path}`, page(path));

  return { visit, file, told, kept, fire, seed };
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

describe("a project's pictures", () => {
  const thumbnail = '/projects/1/media/7/thumbnail?v=abc';

  it('are asked of the server each time: one refused is refused, and forgotten', async () => {
    let allowed = true;
    const { file, kept } = worker(async () =>
      allowed ? page('photo') : new Response('', { status: 403 }),
    );
    await file(thumbnail);
    expect(kept('mtav-pictures-v1')).toEqual([thumbnail]);

    // Someone no longer in the project, or the photo removed
    allowed = false;

    expect((await file(thumbnail)).status).toBe(403);
    expect(kept('mtav-pictures-v1')).toEqual([]);
  });

  it('show as last seen when the server cannot be reached', async () => {
    let reachable = true;
    const { file } = worker(async () => {
      if (!reachable) throw new TypeError('Failed to fetch');
      return page('photo');
    });
    await file(thumbnail);
    reachable = false;

    expect(await (await file(thumbnail)).text()).toBe('photo');
  });

  it('are forgotten with the pages, when someone else signs in or out', async () => {
    const { visit, file, fire, kept } = worker(async () => page('kept'));
    await visit('/projects');
    await file(thumbnail);
    await file('/build/assets/app-1.js');

    await fire('message', { data: 'forget' });

    expect(kept('mtav-pages-v1')).toEqual([]);
    expect(kept('mtav-pictures-v1')).toEqual([]);
    // The app's own files are nobody's
    expect(kept('mtav-files-v1')).toEqual(['/build/assets/app-1.js']);
  });
});

describe("the app's own files", () => {
  /** A server on a build whose files are these */
  const server = (build: string[]) => async (request: Request) =>
    new URL(request.url).pathname === '/build/manifest.json'
      ? Response.json(
          Object.fromEntries(
            build.map((file, index) => [`entry-${index}`, { file, css: [], assets: [] }]),
          ),
        )
      : page(request.url);

  it('are kept once fetched, and answered from there', async () => {
    const network = vi.fn(server(['assets/app-1.js']));
    const { file } = worker(network);
    await file('/build/assets/app-1.js');
    network.mockClear();

    await file('/build/assets/app-1.js');

    expect(network).not.toHaveBeenCalled();
  });

  it("of builds gone by are dropped once a new build's are fetched", async () => {
    let build = ['assets/app-1.js', 'assets/page-1.js'];
    const { file, kept } = worker(async (request) => server(build)(request));
    await file('/build/assets/app-1.js');
    await file('/build/assets/page-1.js');
    await file('/images/logo.png');

    build = ['assets/app-2.js', 'assets/page-1.js'];
    await file('/build/assets/app-2.js');

    expect(kept('mtav-files-v1').sort()).toEqual([
      '/build/assets/app-2.js',
      '/build/assets/page-1.js',
      '/images/logo.png',
    ]);
  });

  it('stay as they are when the build cannot say what it has', async () => {
    const { file, kept } = worker(async (request) =>
      new URL(request.url).pathname === '/build/manifest.json'
        ? new Response('', { status: 404 })
        : page('js'),
    );
    await file('/build/assets/app-1.js');
    await file('/build/assets/app-2.js');

    expect(kept('mtav-files-v1')).toHaveLength(2);
  });

  it('never include pictures an earlier worker kept among them', async () => {
    const { file, fire, kept, seed } = worker(async () => page('x'));
    await file('/images/logo.png');
    seed('mtav-files-v1', '/projects/1/media/7/thumbnail?v=abc');

    await fire('activate', {});

    expect(kept('mtav-files-v1')).toEqual(['/images/logo.png']);
  });
});

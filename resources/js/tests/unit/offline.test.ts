import { connection, heldOffline, keepFor, probe } from '@/lib/offline';
import { afterEach, describe, expect, it, vi } from 'vitest';

const shown = vi.hoisted(() => vi.fn());
vi.mock('@/lib/toasts', () => ({ toast: shown }));

describe('working offline', () => {
  afterEach(() => {
    connection.set({ online: true });
    shown.mockClear();
    vi.unstubAllGlobals();
  });

  it('lets changes through while the server answers', () => {
    expect(heldOffline()).toBe(false);
    expect(shown).not.toHaveBeenCalled();
  });

  it('holds a change while it does not, and says so', () => {
    connection.set({ online: false });

    expect(heldOffline()).toBe(true);
    expect(shown).toHaveBeenCalledWith(expect.objectContaining({ tone: 'warning' }));
  });

  it('asks the server again when a change is held: one failed request proves nothing', () => {
    const asked = vi.fn(() => new Promise(() => {}));
    vi.stubGlobal('fetch', asked);
    connection.set({ online: false });

    heldOffline();

    expect(asked).toHaveBeenCalledWith('/up', { cache: 'no-store' });
  });

  it('is back online as soon as the server answers', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('', { status: 200 })),
    );
    connection.set({ online: false });

    await probe();

    expect(connection.get().online).toBe(true);
  });

  it('stays offline while the server cannot be reached', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => Promise.reject(new TypeError('Failed to fetch'))),
    );
    connection.set({ online: false });

    await probe();

    expect(connection.get().online).toBe(false);
  });
});

describe('the pages kept on the device', () => {
  /** A browser whose worker is controlling the page, or registered and running past it */
  const browser = ({ controlling }: { controlling: boolean }) => {
    const worker = { postMessage: vi.fn() };
    Object.defineProperty(navigator, 'serviceWorker', {
      configurable: true,
      value: {
        controller: controlling ? worker : null,
        getRegistration: async () => ({ active: worker }),
      },
    });
    return worker;
  };

  afterEach(() => {
    Reflect.deleteProperty(navigator, 'serviceWorker');
    localStorage.clear();
  });

  it('are forgotten when someone else signs in, and only then', async () => {
    const worker = browser({ controlling: true });
    await keepFor(1);
    worker.postMessage.mockClear();

    await keepFor(1);
    expect(worker.postMessage).not.toHaveBeenCalled();

    await keepFor(2);
    expect(worker.postMessage).toHaveBeenCalledWith('forget');
  });

  it('are forgotten too on a page the worker does not serve (a hard reload)', async () => {
    browser({ controlling: true });
    await keepFor(1);
    const worker = browser({ controlling: false });

    await keepFor(2);

    expect(worker.postMessage).toHaveBeenCalledWith('forget');
  });

  it('are forgotten later when there is no worker to tell yet', async () => {
    browser({ controlling: true });
    await keepFor(1);
    Object.defineProperty(navigator, 'serviceWorker', {
      configurable: true,
      value: { controller: null, getRegistration: async () => ({ active: null }) },
    });
    await keepFor(2);

    const worker = browser({ controlling: true });
    await keepFor(2);

    expect(worker.postMessage).toHaveBeenCalledWith('forget');
  });
});

import { connection } from '@/lib/offline';
import { dropDevice, pushState, signOut, turnOff, turnOn } from '@/lib/push';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { visit, post, shown } = vi.hoisted(() => ({
  visit: vi.fn(),
  post: vi.fn(),
  shown: vi.fn(),
}));
vi.mock('@inertiajs/react', () => ({ router: { visit, post, on: vi.fn() } }));
vi.mock('@/lib/toasts', () => ({ toast: shown }));
vi.mock('@/lib/route', () => ({ route: (name: string) => name }));

const device = (endpoint: string) => ({
  endpoint,
  unsubscribe: vi.fn(async () => true),
  toJSON: () => ({ endpoint, keys: { p256dh: 'k', auth: 'a' } }),
});

/** A browser that can do it, holding the given subscription (or with no worker at all) */
function browser(subscribed: ReturnType<typeof device> | null, { worker = true } = {}) {
  const pushManager = {
    getSubscription: vi.fn(async () => subscribed),
    subscribe: vi.fn(async () => subscribed),
  };
  vi.stubGlobal('Notification', {
    permission: 'default',
    requestPermission: vi.fn(async () => 'granted'),
  });
  vi.stubGlobal('PushManager', class {});
  Object.defineProperty(navigator, 'serviceWorker', {
    configurable: true,
    value: {
      getRegistration: async () => (worker ? { pushManager } : undefined),
      ready: Promise.resolve({ pushManager }),
    },
  });
}

/** The server's answer to the visit that tells it */
const answers = (how: 'took' | 'refused' | 'never') =>
  visit.mockImplementation((_url, options) => {
    if (how === 'took') options.onSuccess();
    if (how === 'refused') options.onError({ endpoint: 'Not that device' });
    options.onFinish();
  });

describe('notifications on this device', () => {
  beforeEach(() => connection.set({ online: true }));

  afterEach(() => {
    vi.unstubAllGlobals();
    Reflect.deleteProperty(navigator, 'serviceWorker');
  });

  it('are on only if the browser has them and the server sends to it', async () => {
    browser(device('https://push/mine'));

    expect(await pushState(['https://push/mine'])).toBe('on');
    // Another person's device, or one turned off with a new password
    expect(await pushState(['https://push/other'])).toBe('off');
    expect(await pushState([])).toBe('off');
  });

  it('are off with nothing in the browser, even if the server still has it', async () => {
    browser(null);

    expect(await pushState(['https://push/mine'])).toBe('off');
  });

  it('answer off in a browser whose worker never started, rather than never', async () => {
    browser(null, { worker: false });

    expect(await pushState([])).toBe('off');
  });

  it('turn on once the server took them', async () => {
    browser(device('https://push/mine'));
    answers('took');

    expect(await turnOn('key')).toBe('on');
    expect(shown).not.toHaveBeenCalled();
  });

  it('stay off, and say what the server said, when it refuses', async () => {
    const mine = device('https://push/mine');
    browser(mine);
    answers('refused');

    expect(await turnOn('key')).toBe('off');
    expect(mine.unsubscribe).toHaveBeenCalled();
    expect(shown).toHaveBeenCalledWith(
      expect.objectContaining({ tone: 'error', message: 'Not that device' }),
    );
  });

  it('stay off, and say so, when the answer never comes', async () => {
    const mine = device('https://push/mine');
    browser(mine);
    answers('never');

    expect(await turnOn('key')).toBe('off');
    expect(mine.unsubscribe).toHaveBeenCalled();
    expect(shown).toHaveBeenCalledWith(expect.objectContaining({ tone: 'error' }));
  });

  it('stay as they are offline, where a visit would never finish', async () => {
    const mine = device('https://push/mine');
    browser(mine);
    connection.set({ online: false });

    expect(await turnOn('key')).toBe('off');
    expect(await turnOff()).toBe('on');
    expect(visit).not.toHaveBeenCalled();
    expect(mine.unsubscribe).not.toHaveBeenCalled();
  });

  describe('on signing out', () => {
    it('end in the browser, and the server is told which device', async () => {
      const mine = device('https://push/mine');
      browser(mine);

      await signOut();

      expect(mine.unsubscribe).toHaveBeenCalled();
      expect(post).toHaveBeenCalledWith('logout', { endpoint: 'https://push/mine' });
    });

    it('sign out all the same with none, or no way to have them', async () => {
      browser(null);
      await signOut();
      expect(post).toHaveBeenLastCalledWith('logout', { endpoint: null });

      vi.unstubAllGlobals();
      Reflect.deleteProperty(navigator, 'serviceWorker');
      await signOut();
      expect(post).toHaveBeenCalledTimes(2);
    });

    it('sign out all the same when the browser fails to drop them', async () => {
      const mine = device('https://push/mine');
      mine.unsubscribe.mockRejectedValue(new Error('No'));
      browser(mine);

      await signOut();

      expect(post).toHaveBeenCalledWith('logout', { endpoint: 'https://push/mine' });
    });

    it('stay, and nobody signs out, offline', async () => {
      const mine = device('https://push/mine');
      browser(mine);
      connection.set({ online: false });

      await signOut();

      expect(mine.unsubscribe).not.toHaveBeenCalled();
      expect(post).not.toHaveBeenCalled();
    });
  });

  it('dropped, name the device', async () => {
    browser(device('https://push/mine'));

    expect(await dropDevice()).toBe('https://push/mine');
  });
});

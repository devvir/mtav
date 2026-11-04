import { connection, heldOffline, probe } from '@/lib/offline';
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

import { connection, heldOffline } from '@/lib/offline';
import { afterEach, describe, expect, it, vi } from 'vitest';

const shown = vi.hoisted(() => vi.fn());
vi.mock('@/lib/toasts', () => ({ toast: shown }));

describe('working offline', () => {
  afterEach(() => {
    connection.set({ online: true });
    shown.mockClear();
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
});

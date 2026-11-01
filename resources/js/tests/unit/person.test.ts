import { followPerson } from '@/lib/person';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { dropped, handlers, clearHistory } = vi.hoisted(() => ({
  dropped: vi.fn(async () => null),
  handlers: [] as ((event: unknown) => void)[],
  clearHistory: vi.fn(),
}));
vi.mock('@inertiajs/react', () => ({
  router: {
    clearHistory,
    on: (_event: string, handler: (event: unknown) => void) => handlers.push(handler),
  },
}));
vi.mock('@/lib/push', () => ({ dropDevice: dropped }));

const replace = vi.fn();

/** A visit arriving at a page for this person, carrying these messages */
const arrives = async (id: number | null, flash = {}) => {
  handlers.at(-1)!({
    detail: { page: { url: '/here', flash, props: { auth: { user: id ? { id } : null } } } },
  });
  await vi.waitFor(() => undefined);
};

describe('the tab, as people come and go', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.stubGlobal('location', { replace });
  });

  afterEach(() => vi.unstubAllGlobals());

  it('stays as it is for the same person', async () => {
    followPerson(1);
    await arrives(1);

    expect(replace).not.toHaveBeenCalled();
    expect(clearHistory).not.toHaveBeenCalled();
  });

  it('stays as it is for someone signing in: a guest leaves nothing', async () => {
    followPerson(null);
    await arrives(1);
    await arrives(1);

    expect(replace).not.toHaveBeenCalled();
    expect(clearHistory).not.toHaveBeenCalled();
  });

  it('starts over when someone signs out, and when another arrives', async () => {
    followPerson(1);
    await arrives(null);
    expect(replace).toHaveBeenLastCalledWith('/here');
    expect(clearHistory).toHaveBeenCalled();

    replace.mockClear();
    followPerson(1);
    await arrives(2);
    expect(replace).toHaveBeenLastCalledWith('/here');
  });

  it('starts over for someone who signed in on a tab opened as a guest, when they leave', async () => {
    followPerson(null);
    await arrives(1);
    await arrives(null);

    expect(replace).toHaveBeenCalledTimes(1);
  });

  it('shows a message on its way first, and starts over on the next visit', async () => {
    followPerson(1);
    await arrives(null, { error: 'Your account is blocked' });
    expect(replace).not.toHaveBeenCalled();
    expect(clearHistory).toHaveBeenCalled();

    await arrives(null);
    expect(replace).toHaveBeenCalledTimes(1);
  });

  it("keeps the device's notifications for someone whose session merely ended", async () => {
    followPerson(1);
    await vi.waitFor(() => expect(localStorage.getItem('mtav.push-for')).toBe('1'));

    await arrives(null);
    await arrives(1);

    expect(dropped).not.toHaveBeenCalled();
  });

  it("ends the last one's notifications on the device when someone else signs in", async () => {
    followPerson(1);
    await vi.waitFor(() => expect(localStorage.getItem('mtav.push-for')).toBe('1'));

    // The last one's session ended; the tab was closed
    followPerson(null);
    await arrives(2);

    await vi.waitFor(() => expect(dropped).toHaveBeenCalledTimes(1));
    await vi.waitFor(() => expect(localStorage.getItem('mtav.push-for')).toBe('2'));
  });
});

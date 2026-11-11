import { useDetailsInAddress } from '@/lib/detailsInAddress';
import { connection } from '@/lib/offline';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { get, replace } = vi.hoisted(() => ({ get: vi.fn(), replace: vi.fn() }));
vi.mock('@inertiajs/react', () => ({ router: { get, replace, on: vi.fn() } }));
vi.mock('@/lib/toasts', () => ({ toast: vi.fn() }));

/** Where a client-side visit leaves the page's props */
const propsAfter = (props: Record<string, unknown>) => replace.mock.calls[0]![0].props(props);

describe('details in the address', () => {
  beforeEach(() => window.history.replaceState({}, '', '/people?family=3&person=19'));
  afterEach(() => connection.set({ online: true }));

  it('close through the server, which reloads only what they need', () => {
    useDetailsInAddress('person').close();

    expect(get).toHaveBeenCalledWith(
      '/people?family=3',
      {},
      expect.objectContaining({ only: ['person'] }),
    );
    expect(replace).not.toHaveBeenCalled();
  });

  it('close here without the server, the address following', () => {
    connection.set({ online: false });

    useDetailsInAddress('person').close();

    expect(get).not.toHaveBeenCalled();
    expect(replace).toHaveBeenCalledWith(expect.objectContaining({ url: '/people?family=3' }));
    expect(propsAfter({ people: [1], person: { id: 19 } })).toEqual({ people: [1], person: null });
  });

  it('close here when the server turns out to be out of reach', () => {
    useDetailsInAddress('person').close();

    get.mock.calls[0]![2].onNetworkError(new Error('Failed to fetch'));

    expect(replace).toHaveBeenCalledWith(expect.objectContaining({ url: '/people?family=3' }));
  });

  it('open only through the server, which has them', () => {
    connection.set({ online: false });

    useDetailsInAddress('person').open(20);
    get.mock.calls[0]![2].onNetworkError(new Error('Failed to fetch'));

    expect(replace).not.toHaveBeenCalled();
  });
});

import { connection } from '@/lib/offline';
import { useRankingSave } from '@/lib/rankingSave';
import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

interface Options {
  async: boolean;
  onSuccess: (page: { flash: { error?: string } }) => void;
  onFinish: () => void;
}

const put = vi.hoisted(() => vi.fn());
/** Who listens to visits starting ('before') */
const listeners = vi.hoisted(() => new Set<(event: { detail: { visit: unknown } }) => void>());
vi.mock('@inertiajs/react', () => ({
  router: {
    put,
    flushAll: () => undefined,
    on: (type: string, listener: (event: { detail: { visit: unknown } }) => void) => {
      if (type !== 'before') return () => undefined;
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
  },
}));

/** A visit starting, as Inertia announces it: to another page, or a part of this one again */
const visitStarts = (path: string, only: string[] = []) =>
  act(() =>
    listeners.forEach((listener) =>
      listener({
        detail: {
          visit: {
            url: new window.URL(path, window.location.href),
            method: 'get',
            prefetch: false,
            only,
            except: [],
          },
        },
      }),
    ),
  );
vi.mock('@/lib/toasts', () => ({ toast: vi.fn() }));

const unit = (id: number) => ({ id });
const [a, b, c] = [unit(1), unit(2), unit(3)];
const URL = '/projects/1/lottery/preferences';

/** The ids the nth request sent */
const sent = (nth: number) => (put.mock.calls[nth]![1] as { preferences: number[] }).preferences;
const options = (nth: number) => put.mock.calls[nth]![2] as Options;

/** The server's answer to the nth request */
const answer = (nth: number, flash: { error?: string } = {}) =>
  act(() => {
    options(nth).onSuccess({ flash });
    options(nth).onFinish();
  });

const draw = (given = [a, b]) =>
  renderHook(({ given }) => useRankingSave(given, URL), { initialProps: { given } });

describe("saving a family's ranking", () => {
  const fetched = vi.fn(() => Promise.resolve(new Response()));

  beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal('fetch', fetched);
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    connection.set({ online: true });
  });

  it('shows a move at once, says it is saving, and sends only the last of a burst', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    expect(result.current.ranking).toEqual([b, a]);
    expect(result.current.state).toBe('saving');
    expect(put).not.toHaveBeenCalled();

    act(() => vi.advanceTimersByTime(300));
    act(() => result.current.rank([b, a, c]));
    act(() => vi.advanceTimersByTime(600));

    expect(put).toHaveBeenCalledTimes(1);
    expect(sent(0)).toEqual([2, 1, 3]);
  });

  it('never cancels the visit to another page it goes out beside', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    act(() => vi.advanceTimersByTime(600));

    expect(options(0).async).toBe(true);
  });

  it('sends a move still waiting as a visit to another page starts, from this page', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    visitStarts('/projects/1/units');

    expect(put).toHaveBeenCalledTimes(1);
    expect(sent(0)).toEqual([2, 1]);
    expect(options(0).async).toBe(true);
  });

  it('keeps waiting while only a part of this page loads again', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    visitStarts(window.location.pathname, ['ranking']);

    expect(put).not.toHaveBeenCalled();
  });

  it('sends a move still waiting when the page is gone, with no page to bring back', () => {
    const { result, unmount } = draw();

    act(() => result.current.rank([b, a]));
    unmount();

    expect(put).not.toHaveBeenCalled();
    expect(fetched).toHaveBeenCalledWith(
      URL,
      expect.objectContaining({ method: 'PUT', body: '{"preferences":[2,1]}' }),
    );
  });

  it('says it is saved, as the page closes, only when the server took it', async () => {
    const closes = async (answer: Partial<Response>) => {
      fetched.mockResolvedValueOnce(answer as Response);
      const { result, unmount } = draw();
      act(() => result.current.rank([b, a]));
      await act(async () => void window.dispatchEvent(new Event('pagehide')));
      const state = result.current.state;
      unmount();
      return state;
    };

    expect(await closes({ ok: false, status: 419, type: 'basic' })).toBeNull();
    expect(await closes({ ok: false, status: 0, type: 'opaqueredirect' })).toBe('saved');
  });

  it('sends as asked, for another kind of ranking', () => {
    const { result } = renderHook(() =>
      useRankingSave([a, b], '/l/abc/ranking', { field: 'ranking', only: ['me'] }),
    );

    act(() => result.current.rank([b, a]));
    act(() => vi.advanceTimersByTime(600));

    expect(put.mock.calls[0]![1]).toEqual({ ranking: [2, 1] });
    expect(options(0)).toMatchObject({ only: ['me'] });
  });

  it('sends a move still waiting when the page closes, once', () => {
    const { result, unmount } = draw();

    act(() => result.current.rank([b, a]));
    act(() => void window.dispatchEvent(new Event('pagehide')));

    expect(fetched).toHaveBeenCalledTimes(1);
    expect(fetched).toHaveBeenCalledWith(
      URL,
      expect.objectContaining({
        method: 'PUT',
        keepalive: true,
        redirect: 'manual',
        body: '{"preferences":[2,1]}',
      }),
    );
    unmount();
    act(() => vi.advanceTimersByTime(600));
    expect(put).not.toHaveBeenCalled();
  });

  it('sends nothing when the page closes with everything sent', () => {
    draw();

    act(() => void window.dispatchEvent(new Event('pagehide')));

    expect(fetched).not.toHaveBeenCalled();
  });

  it('keeps the move on screen when an older answer arrives, then takes the server’s', () => {
    const { result, rerender } = draw();

    act(() => result.current.rank([b, a]));
    act(() => vi.advanceTimersByTime(600));
    act(() => result.current.rank([a, b]));

    // The answer to the first request, while the newer move is still waiting
    rerender({ given: [b, a] });
    answer(0);
    expect(result.current.ranking).toEqual([a, b]);
    expect(result.current.state).toBe('saving');

    act(() => vi.advanceTimersByTime(600));
    rerender({ given: [c] });
    expect(result.current.ranking).toEqual([a, b]);

    answer(1);
    expect(result.current.ranking).toEqual([c]);
    expect(result.current.state).toBe('saved');
  });

  it('sends one request at a time, the newest order after the one on its way', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    act(() => vi.advanceTimersByTime(600));
    act(() => result.current.rank([a, b]));
    act(() => vi.advanceTimersByTime(600));
    act(() => result.current.rank([b, a, c]));
    act(() => vi.advanceTimersByTime(600));

    expect(put).toHaveBeenCalledTimes(1);

    answer(0);
    expect(put).toHaveBeenCalledTimes(2);
    expect(sent(1)).toEqual([2, 1, 3]);
  });

  it('sends what is left once the answer comes, even after leaving the page', () => {
    const { result, unmount } = draw();

    act(() => result.current.rank([b, a]));
    act(() => vi.advanceTimersByTime(600));
    act(() => result.current.rank([a, b]));
    unmount();
    expect(put).toHaveBeenCalledTimes(1);

    answer(0);
    expect(put).toHaveBeenCalledTimes(1);
    expect(fetched).toHaveBeenCalledWith(
      URL,
      expect.objectContaining({ method: 'PUT', body: '{"preferences":[1,2]}' }),
    );
  });

  it('says it is saved for a moment', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    act(() => vi.advanceTimersByTime(600));
    answer(0);
    expect(result.current.state).toBe('saved');

    act(() => vi.advanceTimersByTime(3000));
    expect(result.current.state).toBeNull();
  });

  it('goes back to the server’s order and says nothing of saving when it is refused', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    act(() => vi.advanceTimersByTime(600));
    answer(0, { error: 'The lottery is running' });

    expect(result.current.ranking).toEqual([a, b]);
    expect(result.current.state).toBeNull();
  });

  it('never stays saving when the request could not finish', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    act(() => vi.advanceTimersByTime(600));
    act(() => options(0).onFinish());

    expect(result.current.ranking).toEqual([a, b]);
    expect(result.current.state).toBeNull();
  });

  it('gives up without sending when the connection went in the meantime', () => {
    const { result } = draw();

    act(() => result.current.rank([b, a]));
    connection.set({ online: false });
    act(() => vi.advanceTimersByTime(600));

    expect(put).not.toHaveBeenCalled();
    expect(result.current.ranking).toEqual([a, b]);
    expect(result.current.state).toBeNull();
  });

  it('takes a fresh order from the server when nothing is on its way', () => {
    const { result, rerender } = draw();

    rerender({ given: [c, a] });

    expect(result.current.ranking).toEqual([c, a]);
  });
});

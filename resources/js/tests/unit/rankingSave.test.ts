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
vi.mock('@inertiajs/react', () => ({ router: { put, on: () => () => undefined } }));
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

  it('sends a move still waiting when the page is left', () => {
    const { result, unmount } = draw();

    act(() => result.current.rank([b, a]));
    unmount();

    expect(put).toHaveBeenCalledTimes(1);
    expect(sent(0)).toEqual([2, 1]);
    expect(options(0).async).toBe(true);
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
    expect(put).toHaveBeenCalledTimes(2);
    expect(sent(1)).toEqual([1, 2]);
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

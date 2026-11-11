import {
  changeableAt,
  goneBetween,
  LEAVING_MS,
  nextClosing,
  rewordedBetween,
  useChangeClock,
  useShownMessages,
  type ChangeableMessage,
} from '@/lib/chatMessages';
import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const at = (iso: string) => Date.parse(iso);
const message = (
  id: number,
  body = `Message ${id}`,
  until: string | null = null,
): ChangeableMessage => ({
  id,
  body,
  changeable_until: until,
});

describe('what went between two answers', () => {
  it('is what the newer one lacks', () => {
    expect(goneBetween([message(1), message(2), message(3)], [message(1), message(3)])).toEqual([
      message(2),
    ]);
  });

  it('is not the oldest that only left the page, as newer ones came', () => {
    expect(
      goneBetween([message(1), message(2), message(3)], [message(2), message(3), message(4)]),
    ).toEqual([]);
  });

  it('is everything, once nothing is left', () => {
    expect(goneBetween([message(1), message(2)], [])).toHaveLength(2);
  });
});

describe('what was reworded', () => {
  it('is what is there before and after, with other words', () => {
    expect(
      rewordedBetween(
        [message(1, 'Hi'), message(2, 'Yo')],
        [message(1, 'Hello'), message(2, 'Yo'), message(3)],
      ),
    ).toEqual([1]);
  });
});

describe('the hour to change a message', () => {
  it('is open until it closes, and only for the writer', () => {
    const own = message(1, 'Hi', '2026-11-11T10:00:00.000Z');

    expect(changeableAt(own, at('2026-11-11T09:59:59.000Z'))).toBe(true);
    expect(changeableAt(own, at('2026-11-11T10:00:00.000Z'))).toBe(false);
    expect(changeableAt(message(2), at('2026-11-11T09:00:00.000Z'))).toBe(false);
  });

  it('closes next for the oldest still open', () => {
    const now = at('2026-11-11T09:30:00.000Z');
    const messages = [
      message(1, 'a', '2026-11-11T09:00:00.000Z'),
      message(2, 'b', '2026-11-11T09:40:00.000Z'),
      message(3, 'c', '2026-11-11T10:10:00.000Z'),
      message(4),
    ];

    expect(nextClosing(messages, now)).toBe(10 * 60_000);
    expect(nextClosing([message(4)], now)).toBeNull();
  });
});

describe('on the page', () => {
  beforeEach(() => vi.useFakeTimers({ now: at('2026-11-11T09:30:00.000Z') }));
  afterEach(() => vi.useRealTimers());

  it('moves on by itself as an hour closes, so its buttons go', () => {
    const messages = [message(1, 'a', '2026-11-11T09:31:00.000Z')];
    const { result } = renderHook(() => useChangeClock(messages));
    expect(changeableAt(messages[0]!, result.current)).toBe(true);

    act(() => vi.advanceTimersByTime(61_000));

    expect(changeableAt(messages[0]!, result.current)).toBe(false);
  });

  it('keeps a deleted message a moment as it leaves, then lets it go', () => {
    const { result, rerender } = renderHook(({ messages }) => useShownMessages(messages), {
      initialProps: { messages: [message(1), message(2), message(3)] },
    });

    rerender({ messages: [message(1), message(3)] });
    expect(result.current.shown.map(({ message, leaving }) => [message.id, leaving])).toEqual([
      [1, false],
      [2, true],
      [3, false],
    ]);

    act(() => vi.advanceTimersByTime(LEAVING_MS));
    expect(result.current.shown.map(({ message }) => message.id)).toEqual([1, 3]);
  });

  it('marks what was just reworded, and nothing at first', () => {
    const { result, rerender } = renderHook(({ messages }) => useShownMessages(messages), {
      initialProps: { messages: [message(1, 'Hi'), message(2, 'Yo')] },
    });
    expect(result.current.reworded.size).toBe(0);

    rerender({ messages: [message(1, 'Hello'), message(2, 'Yo')] });

    expect([...result.current.reworded]).toEqual([1]);
  });
});

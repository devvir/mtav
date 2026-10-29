import { useIdle } from '@/components/plan/idle';
import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const MINUTE = 60_000;

describe('an editor left alone', () => {
  beforeEach(() => vi.useFakeTimers());
  afterEach(() => vi.useRealTimers());

  const idle = (onClose = vi.fn(async () => true)) =>
    renderHook(() => useIdle(true, { warnAfter: 10 * MINUTE, closeAfter: 12 * MINUTE, onClose }));

  it('is warned, counting down, then closed', async () => {
    const onClose = vi.fn(async () => true);
    const { result } = idle(onClose);

    act(() => vi.advanceTimersByTime(9 * MINUTE));
    expect(result.current).toBeNull();

    act(() => vi.advanceTimersByTime(MINUTE + 1000));
    expect(result.current).toBe(119);

    await act(async () => vi.advanceTimersByTime(2 * MINUTE));
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('starts again with any touch of the pointer or the keyboard', () => {
    const { result } = idle();

    act(() => vi.advanceTimersByTime(11 * MINUTE));
    expect(result.current).not.toBeNull();

    act(() => {
      window.dispatchEvent(new Event('keydown'));
      vi.advanceTimersByTime(1000);
    });
    expect(result.current).toBeNull();
  });

  it('stays open when it could not close (what was drawn not saved yet), and counts again', async () => {
    const onClose = vi.fn(async () => false);
    const { result } = idle(onClose);

    await act(async () => vi.advanceTimersByTime(12 * MINUTE + 1000));
    expect(onClose).toHaveBeenCalledTimes(1);
    act(() => vi.advanceTimersByTime(1000));
    expect(result.current).toBeNull();
  });
});

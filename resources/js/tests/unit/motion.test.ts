import { scrollBehavior } from '@/lib/motion';
import { afterEach, describe, expect, it, vi } from 'vitest';

/** A device that asked for less motion, or not */
const device = (still: boolean) =>
  vi
    .spyOn(window, 'matchMedia')
    .mockImplementation(
      (query) => ({ matches: still && query.includes('reduce'), media: query }) as MediaQueryList,
    );

describe('scrolling asked from code', () => {
  afterEach(() => vi.restoreAllMocks());

  it('moves smoothly', () => {
    device(false);
    expect(scrollBehavior()).toBe('smooth');
  });

  it('goes at once for someone who asked for less motion', () => {
    device(true);
    expect(scrollBehavior()).toBe('auto');
  });
});

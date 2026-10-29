import { keepWithin, zoomBox } from '@/components/plan/zoom';
import { describe, expect, it } from 'vitest';

const whole = { x: 0, y: 0, w: 1000, h: 600 };

describe('zooming a plan', () => {
  it('zooms around the point under the pointer, which stays where it was', () => {
    const view = zoomBox(whole, whole, 2, [250, 150]);

    expect(view).toEqual({ x: 125, y: 75, w: 500, h: 300 });
  });

  it('never zooms out past the whole plan, nor in past an eighth of it', () => {
    expect(zoomBox(whole, whole, 0.5, [500, 300])).toEqual(whole);
    expect(zoomBox(whole, whole, 100, [500, 300]).w).toBe(125);
  });

  it('never moves off the plan', () => {
    const view = { x: 0, y: 0, w: 500, h: 300 };

    expect(keepWithin({ ...view, x: -80, y: 900 }, whole)).toEqual({ ...view, x: 0, y: 300 });
  });
});

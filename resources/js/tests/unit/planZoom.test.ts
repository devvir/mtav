import { keepWithin, usePanZoom, zoomBox } from '@/components/plan/zoom';
import { act, renderHook } from '@testing-library/react';
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

describe('moving around a zoomed plan', () => {
  /** A pointer event as a mouse sends it (jsdom has no PointerEvent of its own) */
  const mouse = (type: string, x: number, y: number, buttons: number) =>
    Object.assign(new MouseEvent(type, { bubbles: true, clientX: x, clientY: y, buttons }), {
      pointerId: 1,
      pointerType: 'mouse',
    });

  const zoomedPlan = () => {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    Object.assign(svg, { getScreenCTM: () => ({ a: 1 }) });
    document.body.append(svg);
    const { result } = renderHook(() => usePanZoom({ current: svg }, '0 0 1000 600'));
    act(() => result.current.zoomIn());
    return { svg, view: () => result.current.viewBox };
  };

  it('moves with the plan held down', () => {
    const { svg, view } = zoomedPlan();
    const before = view();

    act(() => {
      svg.dispatchEvent(mouse('pointerdown', 300, 300, 1));
      svg.dispatchEvent(mouse('pointermove', 250, 250, 1));
    });

    expect(view()).not.toBe(before);
  });

  it('stays put once let go, even let go off the plan', () => {
    const { svg, view } = zoomedPlan();

    // Pressed on the plan, released elsewhere: the plan hears no pointerup
    act(() => svg.dispatchEvent(mouse('pointerdown', 300, 300, 1)));
    const before = view();
    act(() => svg.dispatchEvent(mouse('pointermove', 250, 250, 0)));

    expect(view()).toBe(before);
  });
});

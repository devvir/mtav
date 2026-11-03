import { around, frame } from '@/components/domain/PlanView';
import type { Plan } from '@/types/models';
import { describe, expect, it } from 'vitest';

const plan = (items: Plan['items'], polygon: Plan['polygon']): Plan =>
  ({ id: 1, width: 1000, height: 600, polygon, items }) as unknown as Plan;
const canvasEdge: Plan['polygon'] = [
  [0, 0],
  [1000, 0],
  [1000, 600],
  [0, 600],
];
const square = (x: number, y: number) =>
  ({
    id: x,
    floor: 0,
    polygon: [
      [x, y],
      [x + 50, y],
      [x + 50, y + 50],
      [x, y + 50],
    ],
  }) as unknown as Plan['items'][number];

describe('framing a plan', () => {
  it('shows the whole canvas while nothing is drawn', () => {
    expect(frame(plan([], canvasEdge))).toBe('0 0 1000 600');
  });

  it('fits what is drawn, with a little room, when the boundary is the canvas edge', () => {
    expect(frame(plan([square(100, 100), square(300, 100)], canvasEdge))).toBe('60 60 330 130');
  });

  it('keeps a boundary drawn inside the canvas in view', () => {
    const boundary: Plan['polygon'] = [
      [50, 50],
      [700, 50],
      [700, 400],
      [50, 400],
    ];
    expect(frame(plan([square(100, 100)], boundary))).toBe('10 10 730 430');
  });

  it('leaves room past the canvas edge around what is drawn against it', () => {
    expect(frame(plan([square(0, 0)], canvasEdge))).toBe('-40 -40 130 130');
  });
});

describe('the square around a unit', () => {
  const unitAt = (x: number, y: number, w: number, h: number) =>
    ({
      id: 1,
      type: 'unit',
      floor: 2,
      placed: true,
      unit: { id: 7 },
      polygon: [
        [x, y],
        [x + w, y],
        [x + w, y + h],
        [x, y + h],
      ],
    }) as unknown as Plan['items'][number];

  it("centres the unit, at half its side, on the unit's floor", () => {
    expect(around(plan([unitAt(100, 200, 40, 20)], [[0, 0]]), 7)).toEqual({
      viewBox: '80 170 80 80',
      floor: 2,
    });
  });

  it('reaches past the canvas for a unit against its edge, and is nothing for a unit not on the plan', () => {
    expect(around(plan([unitAt(0, 0, 30, 30)], [[0, 0]]), 7)?.viewBox).toBe('-15 -15 60 60');
    expect(around(plan([unitAt(0, 0, 30, 30)], [[0, 0]]), 8)).toBeNull();
  });
});

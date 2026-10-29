import { align } from '@/components/plan/align';

describe('lining up a drag', () => {
  it('pulls a corner within reach onto another, across and down', () => {
    const lined = align([[98, 203]], [[100, 200]], 5);

    expect([lined.dx, lined.dy]).toEqual([2, -3]);
    expect(lined.guides.map((guide) => guide.axis)).toEqual(['x', 'y']);
  });

  it('leaves a corner out of reach where it is, with nothing to show', () => {
    const lined = align([[90, 190]], [[100, 200]], 5);

    expect(lined).toEqual({ dx: 0, dy: 0, guides: [] });
  });

  it('takes the nearest of the corners being dragged', () => {
    const lined = align(
      [
        [0, 0],
        [47, 0],
      ],
      [[50, 500]],
      5,
    );

    expect(lined.dx).toBe(3);
  });

  it('runs a guide from the dragged corner to the one it lines up with', () => {
    const lined = align([[100, 52]], [[100, 300]], 5, { x: true, y: false });

    expect(lined.guides).toEqual([{ axis: 'x', at: 100, from: 52, to: 300 }]);
  });

  it('lines up only along the directions asked for', () => {
    const lined = align([[98, 98]], [[100, 100]], 5, { x: false, y: true });

    expect([lined.dx, lined.dy]).toEqual([0, 2]);
  });
});

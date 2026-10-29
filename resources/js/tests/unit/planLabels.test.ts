import { labelSpot } from '@/components/plan/render';
import type { Point } from '@/types/models';

const rect = (x: number, y: number, w: number, h: number): Point[] => [
  [x, y],
  [x + w, y],
  [x + w, y + h],
  [x, y + h],
];

describe('where a shape says its name', () => {
  it('says it in its middle when nothing covers it', () => {
    expect(labelSpot(rect(0, 0, 200, 100), 'Park', [])).toEqual([100, 50]);
  });

  it('moves it off a shape drawn over the middle', () => {
    const spot = labelSpot(rect(0, 0, 400, 100), 'Park', [rect(150, 0, 100, 100)]);

    expect(spot).not.toBeNull();
    expect(spot![0] < 150 || spot![0] > 250).toBe(true);
  });

  it('says it nowhere when the gaps are too narrow for it', () => {
    const covers = [rect(0, 0, 95, 100), rect(105, 0, 95, 100)];

    expect(labelSpot(rect(0, 0, 200, 100), 'Green area', covers)).toBeNull();
  });
});

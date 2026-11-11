import type { Point } from '@/types/models';
import { bounds, centre, inside } from './geometry';

/** The room a name takes on the plan (its letters are 12 units high) */
const LABEL_HEIGHT = 14;
const labelWidth = (text: string) => text.length * 7 + 6;

/**
 * Where a shape's name is seen whole: its middle, unless shapes drawn over it hide that, then
 * the clear spot nearest the middle; nowhere when no clear spot is large enough (hovering
 * still names it)
 */
export function labelSpot(polygon: Point[], text: string, covers: Point[][]): Point | null {
  const [w, h] = [labelWidth(text) / 2, LABEL_HEIGHT / 2];
  const box = bounds(polygon);
  // Only what reaches into this shape can hide its name
  const over = covers.filter((cover) => {
    const other = bounds(cover);
    return (
      other.minX < box.maxX &&
      other.maxX > box.minX &&
      other.minY < box.maxY &&
      other.maxY > box.minY
    );
  });
  const clear = ([x, y]: Point) => {
    const probes: Point[] = [-1, 0, 1].flatMap((i) =>
      [-1, 0, 1].map((j): Point => [x + i * w, y + j * h]),
    );
    return (
      probes.every((probe) => inside(probe, polygon)) &&
      over.every(
        (cover) =>
          probes.every((probe) => !inside(probe, cover)) &&
          cover.every(([cx, cy]) => Math.abs(cx - x) >= w || Math.abs(cy - y) >= h),
      )
    );
  };

  const middle = centre(polygon);
  if (!over.length || clear(middle)) return middle;
  const steps = 16;
  const spots: Point[] = [];
  for (let i = 0; i <= steps; i++)
    for (let j = 0; j <= steps; j++)
      spots.push([
        box.minX + ((box.maxX - box.minX) * i) / steps,
        box.minY + ((box.maxY - box.minY) * j) / steps,
      ]);
  const near = ([x, y]: Point) => Math.hypot(x - middle[0], y - middle[1]);
  return spots.sort((a, b) => near(a) - near(b)).find(clear) ?? null;
}

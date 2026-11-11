import type { Point } from '@/types/models';

/** Plain polygon geometry ([[x, y], …]), shared by the editor and by how the plan is drawn */

/** The box around a polygon */
export const bounds = (polygon: Point[]) => {
  const xs = polygon.map(([x]) => x);
  const ys = polygon.map(([, y]) => y);

  return {
    minX: Math.min(...xs),
    minY: Math.min(...ys),
    maxX: Math.max(...xs),
    maxY: Math.max(...ys),
  };
};

/** The middle of a polygon's box, where its label goes */
export const centre = (polygon: Point[]): Point => {
  const box = bounds(polygon);
  return [(box.minX + box.maxX) / 2, (box.minY + box.maxY) / 2];
};

/** The angle, in degrees, from a shape's middle to a point */
export const angleTo = (polygon: Point[], [x, y]: Point) => {
  const [cx, cy] = centre(polygon);
  return (Math.atan2(y - cy, x - cx) * 180) / Math.PI;
};

/** A polygon as SVG writes it (a `points` attribute) */
export const points = (polygon: Point[]) => polygon.map(([x, y]) => `${x},${y}`).join(' ');

/** The polygon's area (shoelace), to size trees and depth by */
export const area = (polygon: Point[]) =>
  Math.abs(
    polygon.reduce((sum, [x, y], index) => {
      const [nx, ny] = polygon[(index + 1) % polygon.length]!;
      return sum + x * ny - nx * y;
    }, 0) / 2,
  );

/** Whether a point lies within a polygon */
export const inside = ([x, y]: Point, polygon: Point[]) => {
  let crosses = false;
  for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
    const [xi, yi] = polygon[i]!;
    const [xj, yj] = polygon[j]!;
    if (yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi) crosses = !crosses;
  }
  return crosses;
};

/**
 * The polygon moved in by `by` on every side: stroked round with twice that width, it covers
 * exactly the shape's footprint with eased corners (so shapes still meet edge to edge)
 */
export const inset = (polygon: Point[], by: number): Point[] => {
  const box = bounds(polygon);
  if (box.maxX - box.minX < by * 4 || box.maxY - box.minY < by * 4) return polygon;
  const [cx, cy] = centre(polygon);
  const count = polygon.length;
  const lines = polygon.map((a, index) => {
    const b = polygon[(index + 1) % count]!;
    const length = Math.hypot(b[0] - a[0], b[1] - a[1]) || 1;
    let [nx, ny] = [-(b[1] - a[1]) / length, (b[0] - a[0]) / length];
    // Inward: towards the shape's middle
    if ((cx - (a[0] + b[0]) / 2) * nx + (cy - (a[1] + b[1]) / 2) * ny < 0) [nx, ny] = [-nx, -ny];
    return { at: [a[0] + nx * by, a[1] + ny * by] as Point, along: [b[0] - a[0], b[1] - a[1]] };
  });
  return polygon.map((_, index) => {
    const one = lines[(index - 1 + count) % count]!;
    const two = lines[index]!;
    const cross = one.along[0]! * two.along[1]! - one.along[1]! * two.along[0]!;
    if (Math.abs(cross) < 1e-9) return two.at;
    const t =
      ((two.at[0] - one.at[0]) * two.along[1]! - (two.at[1] - one.at[1]) * two.along[0]!) / cross;
    return [one.at[0] + one.along[0]! * t, one.at[1] + one.along[1]! * t];
  });
};

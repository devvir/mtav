import type { Point } from '@/types/models';
import { bounds, centre } from './geometry';
import { STARTS, type ShapeType } from './kinds';

/**
 * Shapes moved, reshaped, turned and resized as the editor does it: on the grid while
 * snapping (`snap`), else to a tenth of a unit
 */

/** The grid's step, in plan units: what snapping lands on */
export const GRID = 5;

/** A coordinate where it lands: on the grid while snapping, else to a tenth of a unit */
export const round = (value: number, snap: boolean) =>
  snap ? Math.round(value / GRID) * GRID : Math.round(value * 10) / 10;

/** Moved by (dx, dy), its box's top-left corner landing where `round` puts it */
export function translate(polygon: Point[], dx: number, dy: number, snap: boolean): Point[] {
  const box = bounds(polygon);
  const sx = round(box.minX + dx, snap) - box.minX;
  const sy = round(box.minY + dy, snap) - box.minY;
  return polygon.map(([x, y]) => [x + sx, y + sy]);
}

/**
 * An edge moved straight out or in, its two corners together, so a street widens or
 * narrows and a rectangle stays one
 */
export function moveEdge(
  polygon: Point[],
  index: number,
  start: Point,
  at: Point,
  snap: boolean,
): Point[] {
  const a = polygon[index]!;
  const b = polygon[(index + 1) % polygon.length]!;
  const length = Math.hypot(b[0] - a[0], b[1] - a[1]) || 1;
  const normal: Point = [-(b[1] - a[1]) / length, (b[0] - a[0]) / length];
  const distance = (at[0] - start[0]) * normal[0] + (at[1] - start[1]) * normal[1];
  const step = snap ? Math.round(distance / GRID) * GRID : Math.round(distance);
  const shift = ([x, y]: Point): Point => [x + normal[0] * step, y + normal[1] * step];
  const next = [...polygon];
  next[index] = shift(a);
  next[(index + 1) % polygon.length] = shift(b);
  return next;
}

/** Turned around its middle by an angle in degrees (a tenth of a unit is precise enough) */
export function rotate(polygon: Point[], degrees: number): Point[] {
  const [cx, cy] = centre(polygon);
  const angle = (degrees * Math.PI) / 180;
  const [cos, sin] = [Math.cos(angle), Math.sin(angle)];
  const tenth = (value: number) => Math.round(value * 10) / 10;
  return polygon.map(([x, y]) => [
    tenth(cx + (x - cx) * cos - (y - cy) * sin),
    tenth(cy + (x - cx) * sin + (y - cy) * cos),
  ]);
}

/**
 * Scaled from `anchor` so that the corner at `from` goes exactly `to`; as it was when that
 * would flatten it
 */
export function scale(polygon: Point[], anchor: Point, from: Point, to: Point): Point[] {
  const sx = Math.abs(from[0] - anchor[0]) < 1e-6 ? 1 : (to[0] - anchor[0]) / (from[0] - anchor[0]);
  const sy = Math.abs(from[1] - anchor[1]) < 1e-6 ? 1 : (to[1] - anchor[1]) / (from[1] - anchor[1]);
  if (Math.abs(sx) < 0.05 || Math.abs(sy) < 0.05) return polygon;
  return polygon.map(([x, y]) => [
    anchor[0] + (x - anchor[0]) * sx,
    anchor[1] + (y - anchor[1]) * sy,
  ]);
}

/** A corner inserted on an edge, at the point of it closest to `at` */
export function insertVertex(polygon: Point[], edge: number, at: Point, snap: boolean): Point[] {
  const [a, b] = [polygon[edge]!, polygon[(edge + 1) % polygon.length]!];
  const [dx, dy] = [b[0] - a[0], b[1] - a[1]];
  const t = Math.max(
    0,
    Math.min(1, ((at[0] - a[0]) * dx + (at[1] - a[1]) * dy) / (dx * dx + dy * dy || 1)),
  );
  const point: Point = [round(a[0] + t * dx, snap), round(a[1] + t * dy, snap)];
  return [...polygon.slice(0, edge + 1), point, ...polygon.slice(edge + 1)];
}

/** Where a new shape of a kind starts: in the middle of the canvas, in its kind's shape */
export function startShape(
  type: ShapeType,
  canvas: { width: number; height: number },
  snap: boolean,
): Point[] {
  const unit = Math.min(canvas.width, canvas.height);
  const start = STARTS[type];
  const size = (share: number) => Math.max(20, Math.round((unit * share) / GRID) * GRID);
  const [w, h] = [size(start.width), size(start.height)];
  const [cx, cy] = [round(canvas.width / 2, snap), round(canvas.height / 2, snap)];
  return start.triangle
    ? [
        [cx, cy - h / 2],
        [cx + w / 2, cy + h / 2],
        [cx - w / 2, cy + h / 2],
      ]
    : [
        [cx - w / 2, cy - h / 2],
        [cx + w / 2, cy - h / 2],
        [cx + w / 2, cy + h / 2],
        [cx - w / 2, cy + h / 2],
      ];
}

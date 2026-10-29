import type { Point } from '@/types/models';

/** A line drawn while dragging: what the drawing lines up with (x: an upright line) */
export interface Guide {
  axis: 'x' | 'y';
  at: number;
  from: number;
  to: number;
}

const SAME = 0.01;

/**
 * How far to shift the corners being dragged so that one of them lines up with a corner
 * somewhere else, across and down, when it comes within `reach`; and the guides that show it.
 */
export function align(
  moving: Point[],
  anchors: Point[],
  reach: number,
  axes: { x: boolean; y: boolean } = { x: true, y: true },
): { dx: number; dy: number; guides: Guide[] } {
  const nearest = (axis: 0 | 1) => {
    let found: { delta: number; at: number } | null = null;
    for (const point of moving)
      for (const anchor of anchors) {
        const delta = anchor[axis] - point[axis];
        if (Math.abs(delta) <= reach && (!found || Math.abs(delta) < Math.abs(found.delta)))
          found = { delta, at: anchor[axis] };
      }
    return found;
  };
  const x = axes.x ? nearest(0) : null;
  const y = axes.y ? nearest(1) : null;
  const dx = x?.delta ?? 0;
  const dy = y?.delta ?? 0;

  // Each guide runs along every corner that is on it, the dragged ones and the others
  const shifted = moving.map(([px, py]): Point => [px + dx, py + dy]);
  const guide = (axis: 0 | 1, at: number): Guide => {
    const along = [...shifted, ...anchors]
      .filter((point) => Math.abs(point[axis] - at) < SAME)
      .map((point) => point[1 - axis]!);
    return { axis: axis ? 'y' : 'x', at, from: Math.min(...along), to: Math.max(...along) };
  };
  const guides = [...(x ? [guide(0, x.at)] : []), ...(y ? [guide(1, y.at)] : [])];

  return { dx, dy, guides };
}

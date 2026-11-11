import type { Point } from '@/types/models';
import { bounds, centre } from './geometry';
import { PLAY, playOf, treesOf } from './scatter';

/**
 * What each kind of shape draws within it, over its material: a unit's rooms and house mark,
 * a car park's bays, a street's centre line, an entrance's gate, the commons' hall, trees,
 * play equipment. `id` is the plan's defs prefix; `clip` the shape's own outline, by id.
 */

/**
 * A unit's house plan, much simplified and faint (living room, kitchen and bathroom, two
 * bedrooms with their beds), never fighting its name; laid along the unit's own sides, so
 * it turns with it (four-sided units). Its fixtures only where there is room for them.
 */
export function Rooms({ polygon }: { polygon: Point[] }) {
  if (polygon.length !== 4) return null;
  const [a, b, , d] = polygon as [Point, Point, Point, Point];
  // A point of the unit, as a share of its width (u) and depth (v)
  const at = (u: number, v: number): string =>
    `${a[0] + (b[0] - a[0]) * u + (d[0] - a[0]) * v} ${a[1] + (b[1] - a[1]) * u + (d[1] - a[1]) * v}`;
  const line = (u1: number, v1: number, u2: number, v2: number) => `M${at(u1, v1)} L${at(u2, v2)}`;
  const box = (u1: number, v1: number, u2: number, v2: number) =>
    `M${at(u1, v1)} L${at(u2, v1)} L${at(u2, v2)} L${at(u1, v2)} Z`;
  const width = Math.hypot(b[0] - a[0], b[1] - a[1]);
  const depth = Math.hypot(d[0] - a[0], d[1] - a[1]);
  const roomy = Math.min(width, depth) >= 44;

  return (
    <g aria-hidden>
      <path
        className="plan-rooms"
        d={[
          // Living room and the bedrooms; kitchen and bathroom below the living room
          line(0.56, 0, 0.56, 0.42),
          line(0.56, 0.58, 0.56, 1),
          line(0.56, 0.5, 1, 0.5),
          line(0, 0.64, 0.2, 0.64),
          line(0.32, 0.64, 0.56, 0.64),
          line(0.3, 0.64, 0.3, 1),
        ].join(' ')}
      />
      {roomy ? (
        <path
          className="plan-fixtures"
          d={[
            // Beds, a kitchen counter, a shower
            box(0.66, 0.08, 0.86, 0.36),
            box(0.66, 0.6, 0.86, 0.88),
            box(0.04, 0.9, 0.26, 0.96),
            box(0.4, 0.78, 0.52, 0.94),
          ].join(' ')}
        />
      ) : null}
    </g>
  );
}

/** A small house over a unit: what people choose among, recognisable at a glance */
export function HouseMark({ polygon }: { polygon: Point[] }) {
  const box = bounds(polygon);
  const size = Math.max(6, Math.min(12, (box.maxX - box.minX) / 6));
  const x = box.maxX - size - 3;
  const y = box.minY + 3;
  return (
    <path
      className="plan-house"
      d={`M${x} ${y + size * 0.45} L${x + size / 2} ${y} L${x + size} ${y + size * 0.45} V${y + size} H${x} Z`}
      aria-hidden
    />
  );
}

/** A car park's bays: a row along each long side, the aisle between them clear */
export function Bays({ id, polygon, clip }: { id: string; polygon: Point[]; clip: string }) {
  const box = bounds(polygon);
  const wide = box.maxX - box.minX >= box.maxY - box.minY;
  const [from, to] = wide ? [box.minX, box.maxX] : [box.minY, box.maxY];
  const [near, far] = wide ? [box.minY, box.maxY] : [box.minX, box.maxX];
  const depth = Math.min(32, (far - near) * 0.36);
  const lines: [number, number, number, number][] = [];
  for (let at = from + 2; at <= to - 2; at += 15) {
    for (const [a, b] of [
      [near, near + depth],
      [far - depth, far],
    ] as const) {
      lines.push(wide ? [at, a, at, b] : [a, at, b, at]);
    }
  }
  return (
    <g clipPath={`url(#${id}-${clip})`} pointerEvents="none">
      {lines.map(([x1, y1, x2, y2], index) => (
        <line
          key={index}
          x1={x1}
          y1={y1}
          x2={x2}
          y2={y2}
          stroke="#fafaf9"
          strokeWidth={1.1}
          opacity={0.85}
        />
      ))}
    </g>
  );
}

/**
 * An entrance: a gate between two posts, and an arrow through it pointing in. Its shape is
 * a triangle whose first corner is its tip (so it is made, and moving, resizing and turning
 * keep it): the gate stands on the side opposite, the arrow points to the tip.
 */
export function Gate({ polygon }: { polygon: Point[] }) {
  if (polygon.length < 3) return null;
  const tip = polygon[0]!;
  const base = [polygon[1]!, polygon[polygon.length - 1]!];
  const [bx, by] = [(base[0]![0] + base[1]![0]) / 2, (base[0]![1] + base[1]![1]) / 2];
  const width = Math.hypot(base[0]![0] - base[1]![0], base[0]![1] - base[1]![1]);
  const depth = Math.hypot(tip[0] - bx, tip[1] - by);
  // Drawn upright (pointing up, -y), then turned to face the tip
  const angle = (Math.atan2(tip[1] - by, tip[0] - bx) * 180) / Math.PI + 90;
  const half = width / 2;
  const post = Math.max(2.5, width * 0.09);

  return (
    <g transform={`translate(${bx} ${by}) rotate(${angle})`} className="plan-gate" aria-hidden>
      <rect
        x={-half}
        y={-post * 2.6}
        width={post}
        height={post * 2.6}
        rx={post / 3}
        className="plan-gate-post"
      />
      <rect
        x={half - post}
        y={-post * 2.6}
        width={post}
        height={post * 2.6}
        rx={post / 3}
        className="plan-gate-post"
      />
      <path
        d={`M${-half + post} ${-post * 1.6} Q0 ${-post * 3.4} ${half - post} ${-post * 1.6}`}
        className="plan-gate-bar"
      />
      <path
        d={`M0 ${-post * 0.8} V${-depth + post * 1.2} M${-post * 1.2} ${-depth + post * 2.4} L0 ${-depth + post * 1.1} L${post * 1.2} ${-depth + post * 2.4}`}
        className="plan-gate-arrow"
      />
    </g>
  );
}

/** A common space's hall and garden, as large as the space allows, within it */
export function CommonsDrawing({
  id,
  polygon,
  clip,
}: {
  id: string;
  polygon: Point[];
  clip: string;
}) {
  const box = bounds(polygon);
  const [w, h] = [box.maxX - box.minX, box.maxY - box.minY];
  if (w < 30 || h < 22) return null;
  const pad = Math.min(w, h) * 0.08;
  return (
    <g clipPath={`url(#${id}-${clip})`} pointerEvents="none" className="plan-commons">
      <use
        href={`#${id}-commons`}
        x={box.minX + pad}
        y={box.minY + pad}
        width={w - pad * 2}
        height={h - pad * 2}
      />
    </g>
  );
}

/** A street's centre line, dashed, along its length */
export function CentreLine({ id, polygon, clip }: { id: string; polygon: Point[]; clip: string }) {
  const box = bounds(polygon);
  const [cx, cy] = centre(polygon);
  const wide = box.maxX - box.minX >= box.maxY - box.minY;
  return (
    <line
      x1={wide ? box.minX : cx}
      y1={wide ? cy : box.minY}
      x2={wide ? box.maxX : cx}
      y2={wide ? cy : box.maxY}
      stroke="#fde68a"
      strokeWidth={1.6}
      strokeDasharray="7 6"
      clipPath={`url(#${id}-${clip})`}
      pointerEvents="none"
    />
  );
}

/** A green area's trees, scattered within it */
export function Trees({
  id,
  polygon,
  clip,
  seed,
}: {
  id: string;
  polygon: Point[];
  clip: string;
  seed: string;
}) {
  return (
    <g clipPath={`url(#${id}-${clip})`} pointerEvents="none" className="plan-trees">
      {treesOf(polygon, seed).map((tree, index) => (
        <use
          key={index}
          href={`#${id}-tree${tree.kind}`}
          x={tree.x - tree.r}
          y={tree.y - tree.r}
          width={tree.r * 2}
          height={tree.r * 2}
          transform={`rotate(${tree.turn} ${tree.x} ${tree.y})`}
        />
      ))}
    </g>
  );
}

/** A playground's swings, slides, sandpits and roundabouts, scattered within it */
export function PlayEquipment({
  id,
  polygon,
  clip,
  seed,
}: {
  id: string;
  polygon: Point[];
  clip: string;
  seed: string;
}) {
  return (
    <g clipPath={`url(#${id}-${clip})`} pointerEvents="none" className="plan-play">
      {playOf(polygon, seed).map((item, index) => (
        <use
          key={index}
          href={`#${id}-play-${PLAY[item.kind]}`}
          x={item.x - item.r}
          y={item.y - item.r}
          width={item.r * 2}
          height={item.r * 2}
          transform={`rotate(${item.turn} ${item.x} ${item.y})`}
        />
      ))}
    </g>
  );
}

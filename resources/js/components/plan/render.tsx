import type { Point } from '@/types/models';
import type { ReactNode } from 'react';
import { bounds, centre } from './model';

/**
 * How the plan looks, the same in the editor and wherever people read it: each kind of
 * shape has a material of its own (raised units and buildings, grass with trees, asphalt
 * with its centre line, marked parking bays, striped common spaces), lit from the top
 * left, so the plan reads as a place rather than a sketch. High contrast drops textures
 * and depth (styles: .plan-canvas in app.css).
 */

export type Kind =
  | 'unit'
  | 'building'
  | 'street'
  | 'green'
  | 'common'
  | 'parking'
  | 'playground'
  | 'entrance'
  | 'other';

const points = (polygon: Point[]) => polygon.map(([x, y]) => `${x},${y}`).join(' ');

/** The polygon's area (shoelace), to size trees and depth by */
const area = (polygon: Point[]) =>
  Math.abs(
    polygon.reduce((sum, [x, y], index) => {
      const [nx, ny] = polygon[(index + 1) % polygon.length]!;
      return sum + x * ny - nx * y;
    }, 0) / 2,
  );

const inside = ([x, y]: Point, polygon: Point[]) => {
  let crosses = false;
  for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
    const [xi, yi] = polygon[i]!;
    const [xj, yj] = polygon[j]!;
    if (yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi) crosses = !crosses;
  }
  return crosses;
};

/** A repeatable sequence of numbers between 0 and 1, from a seed (the shape's identity) */
const random = (seed: string) => {
  let state = [...seed].reduce((hash, char) => (hash * 31 + char.charCodeAt(0)) >>> 0, 7);
  return () => {
    state = (state * 1664525 + 1013904223) >>> 0;
    return state / 2 ** 32;
  };
};

/** Where the trees of a green area stand: scattered, never outside it, more as it grows */
function treesOf(polygon: Point[], seed: string) {
  const box = bounds(polygon);
  const size = Math.sqrt(area(polygon));
  const radius = Math.max(7, Math.min(18, size / 7));
  const step = radius * 2.1;
  const next = random(seed);
  const trees: { x: number; y: number; r: number; kind: number; turn: number }[] = [];
  for (let y = box.minY + radius; y <= box.maxY - radius * 0.6; y += step) {
    for (let x = box.minX + radius; x <= box.maxX - radius * 0.6; x += step) {
      const spot: Point = [x + (next() - 0.5) * step * 0.7, y + (next() - 0.5) * step * 0.7];
      // A few clearings, so it never looks planted in rows
      if (next() < 0.22 || !inside(spot, polygon)) continue;
      trees.push({
        x: spot[0],
        y: spot[1],
        r: radius * (0.75 + next() * 0.45),
        kind: Math.floor(next() * 3),
        turn: Math.round(next() * 360),
      });
    }
  }
  return trees.slice(0, 60);
}

/** Canopies seen from above: crowns as [x, y, radius] in a 20 × 20 box */
const TREES: [number, number, number][][] = [
  [
    [-3, -2, 5.5],
    [3, -3, 5],
    [2, 3, 5.5],
    [-3.5, 3.5, 4.5],
    [0, 0, 5],
  ],
  [
    [-2.5, -2.5, 6],
    [3.5, 0, 5.2],
    [-1, 3.8, 5],
  ],
  [
    [0, -4, 4.5],
    [-4, 0, 4.5],
    [4, 0.5, 4.5],
    [0, 4, 4.5],
    [0, 0, 5.5],
  ],
];

/** The materials every shape on a plan draws from; ids carry the plan's own prefix */
export function PlanDefs({ id }: { id: string }) {
  return (
    <defs>
      <filter id={`${id}-shadow`} x="-20%" y="-20%" width="140%" height="150%">
        <feDropShadow dx="1.5" dy="3" stdDeviation="2.2" floodColor="#1c1917" floodOpacity="0.28" />
      </filter>
      <filter id={`${id}-soft`} x="-30%" y="-30%" width="160%" height="160%">
        <feGaussianBlur stdDeviation="1.2" />
      </filter>
      {/* Light from the top left, laid over any colour */}
      <linearGradient id={`${id}-light`} x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stopColor="#fff" stopOpacity="0.45" />
        <stop offset="0.45" stopColor="#fff" stopOpacity="0.06" />
        <stop offset="1" stopColor="#000" stopOpacity="0.14" />
      </linearGradient>
      {/* Three greens of canopy, each lit from the top left */}
      {[
        ['#a3d977', '#5b9a3c', '#3d7429'],
        ['#8fcf86', '#4f9a52', '#2f6b3a'],
        ['#b7d98a', '#73a34a', '#4c7a2e'],
      ].map(([light, mid, dark], kind) => (
        <radialGradient key={kind} id={`${id}-canopy${kind}`} cx="0.35" cy="0.3" r="0.8">
          <stop offset="0" stopColor={light} />
          <stop offset="0.6" stopColor={mid} />
          <stop offset="1" stopColor={dark} />
        </radialGradient>
      ))}
      <pattern id={`${id}-land`} width="16" height="16" patternUnits="userSpaceOnUse">
        <rect width="16" height="16" fill="var(--plan-land)" />
        <circle cx="3" cy="4" r="0.7" fill="var(--plan-land-grain)" />
        <circle cx="11" cy="9" r="0.6" fill="var(--plan-land-grain)" />
        <circle cx="6" cy="13" r="0.5" fill="var(--plan-land-grain)" />
      </pattern>
      <pattern id={`${id}-grass`} width="12" height="12" patternUnits="userSpaceOnUse">
        <rect width="12" height="12" fill="#9bc97f" />
        <path
          d="M2 8 l0.8 -2.6 M6.5 10.5 l0.9 -2.6 M9 4.5 l0.8 -2.4 M4 3.5 l-0.8 -2 M10.5 9 l-0.7 -2"
          stroke="#6f9f56"
          strokeWidth="0.7"
          strokeLinecap="round"
          opacity="0.8"
        />
        <circle cx="7.5" cy="6.5" r="0.6" fill="#c8e3a8" />
      </pattern>
      <pattern id={`${id}-asphalt`} width="12" height="12" patternUnits="userSpaceOnUse">
        <rect width="12" height="12" fill="#57534e" />
        <circle cx="2" cy="3" r="0.6" fill="#78716c" />
        <circle cx="8" cy="5" r="0.5" fill="#44403c" />
        <circle cx="5" cy="10" r="0.6" fill="#78716c" />
        <circle cx="10" cy="11" r="0.4" fill="#a8a29e" />
      </pattern>
      <pattern
        id={`${id}-stripes`}
        width="8"
        height="8"
        patternUnits="userSpaceOnUse"
        patternTransform="rotate(45)"
      >
        <rect width="8" height="8" fill="transparent" />
        <rect width="3" height="8" fill="#fff" opacity="0.35" />
      </pattern>
      <pattern id={`${id}-play`} width="14" height="14" patternUnits="userSpaceOnUse">
        <rect width="14" height="14" fill="#fb923c" />
        <circle cx="4" cy="4" r="1.6" fill="#fdba74" />
        <circle cx="11" cy="10" r="1.3" fill="#fed7aa" />
      </pattern>
      {/* A tree from above: its shade on the ground, then a canopy of a few crowns */}
      {TREES.map((crowns, kind) => (
        <symbol key={kind} id={`${id}-tree${kind}`} viewBox="-10 -10 20 20" overflow="visible">
          <g transform="translate(2.6 3.2)" filter={`url(#${id}-soft)`} opacity="0.35">
            {crowns.map(([x, y, r], index) => (
              <circle key={index} cx={x} cy={y} r={r} fill="#1f3d17" />
            ))}
          </g>
          {crowns.map(([x, y, r], index) => (
            <circle
              key={index}
              cx={x}
              cy={y}
              r={r}
              fill={`url(#${id}-canopy${kind})`}
              stroke="#2c5221"
              strokeWidth="0.5"
              strokeOpacity="0.45"
            />
          ))}
        </symbol>
      ))}
    </defs>
  );
}

/** The ground the project stands on: its boundary, textured, its edge a property line */
export function PlanGround({
  id,
  polygon,
  ...rest
}: { id: string; polygon: Point[] } & React.SVGProps<SVGPolygonElement>) {
  return (
    <polygon
      points={points(polygon)}
      fill={`url(#${id}-land)`}
      stroke="var(--mtav-border-strong)"
      strokeWidth={2}
      strokeDasharray="10 5"
      {...rest}
    />
  );
}

/** A small house over a unit: what people choose among, recognisable at a glance */
function HouseMark({ polygon }: { polygon: Point[] }) {
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
function Bays({ id, polygon, clip }: { id: string; polygon: Point[]; clip: string }) {
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

/** A door, over an entrance's name */
function DoorMark({ polygon }: { polygon: Point[] }) {
  const [cx, cy] = centre(polygon);
  return (
    <path
      className="plan-door"
      d={`M${cx - 4} ${cy - 3} V${cy - 12} a4 4 0 0 1 8 0 V${cy - 3} Z`}
      aria-hidden
    />
  );
}

/** A street's centre line, dashed, along its length */
function CentreLine({ id, polygon, clip }: { id: string; polygon: Point[]; clip: string }) {
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

/**
 * One shape of the plan in its material. `fill` overrides the colour (a unit the reader
 * should notice); `color` is the one its admins chose, tinting units, buildings and others.
 */
export function PlanShape({
  id,
  seed,
  kind,
  polygon,
  color,
  fill,
  label,
  selected,
  children,
}: {
  /** The plan's defs prefix */
  id: string;
  /** What keeps its trees in place between renders */
  seed: string;
  kind: Kind;
  polygon: Point[];
  color?: string;
  fill?: string;
  label?: string | null;
  selected?: boolean;
  children?: ReactNode;
}) {
  const box = bounds(polygon);
  const shape = points(polygon);
  const clip = `clip-${seed.replace(/[^a-z0-9-]/gi, '')}`;
  const raised = kind === 'unit' || kind === 'building' || kind === 'other';
  // Higher for buildings than for units; never more than a sliver of the shape
  const depth = raised
    ? Math.max(
        2,
        Math.min(
          kind === 'building' ? 8 : 5,
          Math.min(box.maxX - box.minX, box.maxY - box.minY) * 0.1,
        ),
      )
    : 0;
  const base =
    fill ??
    {
      unit: color ?? 'var(--plan-unit)',
      building: color ?? '#d6d3d1',
      other: color ?? '#e9d5ff',
      street: `url(#${id}-asphalt)`,
      green: `url(#${id}-grass)`,
      common: color ?? '#fcd34d',
      parking: `url(#${id}-asphalt)`,
      playground: `url(#${id}-play)`,
      entrance: color ?? '#a5b4fc',
    }[kind];
  const [cx, cy] = centre(polygon);

  return (
    <g className="plan-item" data-kind={kind} data-selected={selected || undefined}>
      <clipPath id={`${id}-${clip}`}>
        <polygon points={shape} />
      </clipPath>

      {raised ? (
        <>
          {/* Its sides, seen from a little to the south */}
          <polygon
            points={shape}
            transform={`translate(0 ${depth})`}
            fill={base}
            className="plan-side"
            filter={`url(#${id}-shadow)`}
          />
          <polygon points={shape} transform={`translate(0 ${depth})`} className="plan-side-shade" />
        </>
      ) : null}

      <polygon
        points={shape}
        fill={base}
        className="plan-top"
        filter={raised ? undefined : `url(#${id}-shadow)`}
      />
      {kind === 'common' ? <polygon points={shape} fill={`url(#${id}-stripes)`} /> : null}
      {kind === 'street' ? <CentreLine id={id} polygon={polygon} clip={clip} /> : null}
      {kind === 'parking' ? <Bays id={id} polygon={polygon} clip={clip} /> : null}
      {kind === 'green' ? (
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
      ) : null}
      <polygon
        points={shape}
        fill={`url(#${id}-light)`}
        className="plan-light"
        pointerEvents="none"
      />
      <polygon points={shape} className="plan-outline" fill="none" />
      {kind === 'unit' ? <HouseMark polygon={polygon} /> : null}
      {kind === 'entrance' ? <DoorMark polygon={polygon} /> : null}

      {label ? (
        <text x={cx} y={cy} className="plan-label" textAnchor="middle" dominantBaseline="middle">
          {label}
        </text>
      ) : null}
      {children}
    </g>
  );
}

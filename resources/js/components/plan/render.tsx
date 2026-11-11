import type { Point } from '@/types/models';
import type { ReactNode, SVGProps } from 'react';
import {
  Bays,
  CentreLine,
  CommonsDrawing,
  Gate,
  HouseMark,
  PlayEquipment,
  Rooms,
  Trees,
} from './features';
import { inset, points } from './geometry';
import { kindLabel, type ShapeType } from './kinds';
import { labelSpot } from './labels';

/**
 * How the plan looks, the same in the editor and wherever people read it: each kind of
 * shape has a material of its own (raised units and buildings, grass with trees, asphalt
 * with its centre line, marked parking bays, striped common spaces), lit from the top
 * left, so the plan reads as a place rather than a sketch. The materials are `PlanDefs`
 * (materials.tsx), what each kind draws within it is in features.tsx. High contrast drops
 * textures and depth (styles: .plan-canvas in app.css).
 */

/** The ground the project stands on: its boundary, textured, its edge a property line */
export function PlanGround({
  id,
  polygon,
  ...rest
}: { id: string; polygon: Point[] } & SVGProps<SVGPolygonElement>) {
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
  hint,
  covers,
  selected,
  children,
}: {
  /** The plan's defs prefix */
  id: string;
  /** What keeps its trees in place between renders */
  seed: string;
  kind: ShapeType;
  polygon: Point[];
  color?: string;
  fill?: string;
  label?: string | null;
  /** What hovering it says (its label, else its kind) */
  hint?: string;
  /** The shapes drawn over it, that its name keeps clear of */
  covers?: Point[][];
  selected?: boolean;
  children?: ReactNode;
}) {
  const shape = points(polygon);
  const clip = `clip-${seed.replace(/[^a-z0-9-]/gi, '')}`;
  // Standing shapes cast a longer shadow than what lies on the ground
  const raised = kind === 'unit' || kind === 'building' || kind === 'other' || kind === 'common';
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
      entrance: 'transparent',
    }[kind];
  // A name left empty shows nothing (hovering still says what it is)
  const name = label || null;
  const spot = name ? labelSpot(polygon, name, covers ?? []) : null;

  return (
    <g
      className="plan-item"
      data-kind={kind}
      data-selected={selected || undefined}
      data-hint={hint ?? (label || kindLabel(kind)) ?? undefined}
    >
      <clipPath id={`${id}-${clip}`}>
        <polygon points={shape} />
      </clipPath>

      {/* Drawn a little within its edge, then stroked round: corners eased, footprint exact */}
      <polygon
        points={raised ? points(inset(polygon, 1.5)) : shape}
        fill={base}
        stroke={raised ? base : undefined}
        strokeWidth={raised ? 3 : undefined}
        strokeLinejoin="round"
        className="plan-top"
        filter={`url(#${id}-${raised ? 'shadow' : 'ground-shadow'})`}
      />
      {kind === 'common' ? (
        <>
          <polygon points={shape} fill={`url(#${id}-stripes)`} />
          <CommonsDrawing id={id} polygon={polygon} clip={clip} />
        </>
      ) : null}
      {kind === 'unit' ? <Rooms polygon={polygon} /> : null}
      {kind === 'street' ? <CentreLine id={id} polygon={polygon} clip={clip} /> : null}
      {kind === 'parking' ? <Bays id={id} polygon={polygon} clip={clip} /> : null}
      {kind === 'playground' ? (
        <PlayEquipment id={id} polygon={polygon} clip={clip} seed={seed} />
      ) : null}
      {kind === 'green' ? <Trees id={id} polygon={polygon} clip={clip} seed={seed} /> : null}
      <polygon
        points={shape}
        fill={`url(#${id}-light)`}
        className="plan-light"
        pointerEvents="none"
      />
      <polygon points={shape} className="plan-outline" fill="none" />
      {kind === 'unit' ? <HouseMark polygon={polygon} /> : null}
      {kind === 'entrance' ? <Gate polygon={polygon} /> : null}

      {spot && name ? (
        <text
          x={spot[0]}
          y={spot[1]}
          className="plan-label"
          textAnchor="middle"
          dominantBaseline="middle"
        >
          {name}
        </text>
      ) : null}
      {children}
    </g>
  );
}

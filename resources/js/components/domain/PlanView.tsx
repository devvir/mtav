import { bounds, DEFAULT_COLORS } from '@/components/plan/model';
import { PlanDefs, PlanGround, PlanShape, type Kind } from '@/components/plan/render';
import { _ } from '@/lib/translations';
import type { Plan, PlanItem } from '@/types/models';
import { useId } from 'react';

/**
 * The part of the canvas worth showing: every shape on every floor (switching floors keeps
 * the frame), and the boundary unless it is only the canvas's edge, with a little room.
 */
export const frame = (plan: Plan) => {
  const whole = `0 0 ${plan.width} ${plan.height}`;
  const edge = bounds(plan.polygon);
  const boundaryIsCanvas =
    edge.minX <= 0 && edge.minY <= 0 && edge.maxX >= plan.width && edge.maxY >= plan.height;
  const drawn = [
    ...plan.items.flatMap((item) => item.polygon),
    ...(boundaryIsCanvas ? [] : plan.polygon),
  ];
  if (!drawn.length) return whole;

  const box = bounds(drawn);
  const room = Math.max(plan.width, plan.height) * 0.04;
  const x = Math.max(0, box.minX - room);
  const y = Math.max(0, box.minY - room);
  const width = Math.min(plan.width, box.maxX + room) - x;
  const height = Math.min(plan.height, box.maxY + room) - y;

  return `${x} ${y} ${width} ${height}`;
};

/**
 * The project plan, read-only: the boundary and every shape, units labelled. Units of the
 * viewer's type stand out; the viewer's own unit most. Given a ranking, each of its units
 * shows its place in it.
 */
export function PlanView({
  plan,
  floor = 0,
  highlightType,
  ownUnit,
  ranks,
}: {
  plan: Plan;
  floor?: number;
  highlightType?: number | null;
  ownUnit?: number | null;
  /** A unit's place in the family's ranking, by unit id */
  ranks?: Map<number, number>;
}) {
  const id = `plan${useId().replace(/:/g, '')}`;
  // A colour its admins chose (a kind's own default is its material, not a choice)
  const tint = (item: PlanItem) => {
    const color = item.metadata?.color as string | undefined;
    return color && color !== DEFAULT_COLORS[item.type as Kind] ? color : undefined;
  };
  // What the reader should notice: their own unit, then the units of their type
  const fill = (item: PlanItem) => {
    if (item.unit && item.unit.id === ownUnit) return 'var(--mtav-positive)';
    if (item.unit && highlightType && item.unit.unit_type_id === highlightType)
      return 'var(--mantine-color-brand-4)';
    return undefined;
  };

  return (
    <svg
      viewBox={frame(plan)}
      className="plan-canvas ds-surface"
      role="img"
      aria-label={_('Project plan')}
    >
      <PlanDefs id={id} />
      <PlanGround id={id} polygon={plan.polygon} />
      {/* The floors beneath this one, faintly: what it stands on */}
      {floor > 0 ? (
        <g className="plan-beneath" aria-hidden>
          {plan.items
            .filter((item) => item.floor < floor)
            .map((item) => (
              <g key={item.id} data-depth={Math.min(floor - item.floor, 3)}>
                <PlanShape
                  id={id}
                  seed={String(item.id)}
                  kind={item.type as Kind}
                  polygon={item.polygon}
                  color={tint(item)}
                />
              </g>
            ))}
        </g>
      ) : null}
      {plan.items
        .filter((item) => item.floor === floor)
        .map((item) => {
          const rank = item.unit ? ranks?.get(item.unit.id) : undefined;
          const box = bounds(item.polygon);
          return (
            <PlanShape
              key={item.id}
              id={id}
              seed={String(item.id)}
              kind={item.type as Kind}
              polygon={item.polygon}
              color={tint(item)}
              fill={fill(item)}
              label={item.unit?.identifier ?? item.name}
            >
              {rank ? (
                <g className="plan-rank" data-first={rank === 1 || undefined} aria-hidden>
                  <circle cx={box.minX + 9} cy={box.minY + 9} r={8} />
                  <text
                    x={box.minX + 9}
                    y={box.minY + 9}
                    textAnchor="middle"
                    dominantBaseline="central"
                    fontSize={10}
                    fontWeight={800}
                  >
                    {rank}
                  </text>
                </g>
              ) : null}
              <title>
                {item.unit
                  ? [
                      item.unit.identifier,
                      rank ? _('number {position} in your ranking', { position: rank }) : null,
                      item.unit.family?.name,
                    ]
                      .filter(Boolean)
                      .join(' · ')
                  : (item.name ?? '')}
              </title>
            </PlanShape>
          );
        })}
    </svg>
  );
}

import { bounds, centre } from '@/components/plan/model';
import { _ } from '@/lib/translations';
import type { Plan, PlanItem, Point } from '@/types/models';

const points = (polygon: Point[]) => polygon.map(([x, y]) => `${x},${y}`).join(' ');

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
  const fill = (item: PlanItem) => {
    if (item.unit && item.unit.id === ownUnit) return 'var(--mtav-positive)';
    if (item.unit && highlightType && item.unit.unit_type_id === highlightType)
      return 'var(--mantine-color-brand-4)';
    if (item.unit) return 'var(--mtav-border-strong)';
    return (item.metadata?.color as string) ?? 'var(--mtav-surface-2)';
  };

  return (
    <svg
      viewBox={frame(plan)}
      className="plan-canvas ds-surface"
      role="img"
      aria-label={_('Project plan')}
    >
      <polygon
        points={points(plan.polygon)}
        fill="var(--mtav-surface-2)"
        stroke="var(--mtav-border-strong)"
        strokeWidth={2}
      />
      {plan.items
        .filter((item) => item.floor === floor)
        .map((item) => {
          const [x, y] = centre(item.polygon);
          const rank = item.unit ? ranks?.get(item.unit.id) : undefined;
          const box = bounds(item.polygon);
          return (
            <g key={item.id}>
              <polygon
                points={points(item.polygon)}
                fill={fill(item)}
                fillOpacity={0.85}
                stroke="var(--mtav-text)"
                strokeOpacity={0.35}
                strokeWidth={1}
              />
              {item.unit || item.name ? (
                <text
                  x={x}
                  y={y}
                  textAnchor="middle"
                  dominantBaseline="middle"
                  fontSize={12}
                  fontWeight={650}
                  fill="var(--mtav-text)"
                >
                  {item.unit?.identifier ?? item.name}
                </text>
              ) : null}
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
            </g>
          );
        })}
    </svg>
  );
}

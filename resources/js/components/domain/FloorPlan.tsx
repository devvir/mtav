import { floorLabel } from '@/components/plan/floors';
import { _ } from '@/lib/translations';
import type { Plan } from '@/types/models';
import { Group, Text } from '@mantine/core';
import { useState, type ComponentProps } from 'react';
import { PlanView } from './PlanView';

/** The plan, one floor at a time (a choice of floors when there are several) */
export function FloorPlan({
  plan,
  ...view
}: { plan: Plan } & Omit<ComponentProps<typeof PlanView>, 'plan' | 'floor'>) {
  const floors = [...new Set(plan.items.map((item) => item.floor))].sort((a, b) => a - b);
  const [floor, setFloor] = useState(floors[0] ?? 0);

  return (
    <>
      {floors.length > 1 ? (
        <Group gap="xs" mb="md" role="tablist" aria-label={_('Floors')}>
          {floors.map((value) => (
            <button
              key={value}
              type="button"
              role="tab"
              className="ds-pill floor-tab"
              aria-selected={floor === value}
              data-active={floor === value || undefined}
              onClick={() => setFloor(value)}
            >
              {floorLabel(value)}
            </button>
          ))}
        </Group>
      ) : floors.length && floors[0] !== 0 ? (
        <Text fw={650} mb="md">
          {floorLabel(floors[0]!)}
        </Text>
      ) : null}
      <PlanView plan={plan} floor={floor} {...view} />
    </>
  );
}

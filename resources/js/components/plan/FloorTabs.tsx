import { _ } from '@/lib/translations';
import { Button, Group } from '@mantine/core';
import { IconStack2 } from '@tabler/icons-react';
import { floorLabel } from './floors';
import type { PlanModel } from './model';

/** The plan's floors, the one being drawn chosen, and one more added above the highest */
export function FloorTabs({ model }: { model: PlanModel }) {
  return (
    <Group gap="xs" data-tour="plan.floors" role="tablist" aria-label={_('Floors')}>
      {model.allFloors.map((floor) => (
        <button
          key={floor}
          type="button"
          role="tab"
          className="ds-pill floor-tab"
          aria-selected={model.floor === floor}
          data-active={model.floor === floor || undefined}
          onClick={() => model.showFloor(floor)}
        >
          {floorLabel(floor)}
        </button>
      ))}
      <Button
        variant="subtle"
        size="compact-sm"
        leftSection={<IconStack2 size={16} />}
        disabled={!model.canAddFloor}
        onClick={() => model.addFloor()}
      >
        {_('Add a floor')}
      </Button>
    </Group>
  );
}

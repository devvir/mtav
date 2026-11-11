import { _ } from '@/lib/translations';
import { ActionIcon, Button, Group, Switch, Tooltip } from '@mantine/core';
import { IconArrowBackUp, IconArrowForwardUp } from '@tabler/icons-react';
import type { PlanModel } from './model';

/** The editor's tools, there when reached for, never in the way: undo, redo, the boundary, the grid */
export function PlanToolbar({ model }: { model: PlanModel }) {
  return (
    <Group gap={4} className="plan-tools">
      <Tooltip label={_('Undo')}>
        <ActionIcon
          variant="subtle"
          color="gray"
          disabled={!model.canUndo}
          aria-label={_('Undo')}
          onClick={() => model.undo()}
        >
          <IconArrowBackUp size={18} />
        </ActionIcon>
      </Tooltip>
      <Tooltip label={_('Redo')}>
        <ActionIcon
          variant="subtle"
          color="gray"
          disabled={!model.canRedo}
          aria-label={_('Redo')}
          onClick={() => model.redo()}
        >
          <IconArrowForwardUp size={18} />
        </ActionIcon>
      </Tooltip>
      <Button
        variant={model.editingBoundary ? 'light' : 'subtle'}
        color={model.editingBoundary ? undefined : 'gray'}
        size="compact-xs"
        aria-pressed={model.editingBoundary}
        onClick={() => model.select(model.editingBoundary ? null : 'boundary')}
      >
        {_('Edit the boundary')}
      </Button>
      <Switch
        size="xs"
        className="plan-snap"
        data-on={model.snap || undefined}
        label={_('Snap to grid')}
        checked={model.snap}
        onChange={(event) => model.setSnap(event.currentTarget.checked)}
      />
    </Group>
  );
}

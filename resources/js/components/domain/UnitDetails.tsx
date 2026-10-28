import { UnitForm } from '@/components/forms/UnitForm';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Unit, UnitType } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { Anchor, Button, Divider, Group, Text, Tooltip } from '@mantine/core';
import { IconPencil, IconTrash } from '@tabler/icons-react';
import { useState } from 'react';

/** A unit: its type and who received it; admins edit or remove it */
export function UnitDetails({
  unit,
  unitTypes,
}: {
  unit: Unit;
  /** For admins, who edit units here */
  unitTypes: UnitType[] | null;
}) {
  const [editing, setEditing] = useState(false);
  const remove = async () => {
    if (
      await confirm({
        title: _('Remove unit {name}?', { name: unit.identifier }),
        confirmLabel: _('Remove'),
        danger: true,
      })
    )
      router.delete(route('manage.units.destroy', unit.id));
  };

  // Editing replaces the details in the same dialog (one dialog at a time)
  if (editing && unitTypes)
    return <UnitForm unit={unit} unitTypes={unitTypes} onDone={() => setEditing(false)} />;

  return (
    <>
      {unit.type ? (
        <Text c="dimmed" mb="sm">
          {unit.type.name}
        </Text>
      ) : null}
      {unit.family ? (
        <Text>
          {_('Assigned to')}{' '}
          <Anchor
            component={Link}
            href={route('families.index', { family: unit.family.id })}
            fw={650}
          >
            {unit.family.name}
          </Anchor>
        </Text>
      ) : (
        <Text c="dimmed">
          {_('Not assigned yet: the lottery decides which family receives it.')}
        </Text>
      )}

      {unitTypes ? (
        <>
          <Divider my="lg" />
          <Group gap="sm">
            <Button
              variant="default"
              leftSection={<IconPencil size={18} />}
              onClick={() => setEditing(true)}
            >
              {_('Edit')}
            </Button>
            <Tooltip label={_('A family received it')} disabled={!unit.family} withinPortal>
              <Button
                variant="default"
                color="red"
                c={unit.family ? undefined : 'red'}
                leftSection={<IconTrash size={18} />}
                disabled={Boolean(unit.family)}
                onClick={remove}
              >
                {_('Remove')}
              </Button>
            </Tooltip>
          </Group>
        </>
      ) : null}
    </>
  );
}

import { UnitForm } from '@/components/forms/UnitForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Unit, UnitType } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { Anchor, Button, Card, Group, Modal, Text, Tooltip } from '@mantine/core';
import { IconPencil, IconTrash } from '@tabler/icons-react';
import { useState } from 'react';

/** A unit: its type and who received it; admins edit or remove it here */
export default function UnitShow({
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

  return (
    <>
      <PageHeader
        title={unit.identifier}
        description={unit.type?.name}
        back={{ href: route('units.index'), label: _('Units') }}
        actions={
          unitTypes ? (
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
          ) : null
        }
      />
      <Card p="lg" maw={560}>
        {unit.family ? (
          <Text>
            {_('Assigned to')}{' '}
            <Anchor component={Link} href={route('families.show', unit.family.id)} fw={650}>
              {unit.family.name}
            </Anchor>
          </Text>
        ) : (
          <Text c="dimmed">
            {_('Not assigned yet: the lottery decides which family receives it.')}
          </Text>
        )}
      </Card>

      {unitTypes ? (
        <Modal opened={editing} onClose={() => setEditing(false)} title={unit.identifier}>
          <UnitForm unit={unit} unitTypes={unitTypes} onDone={() => setEditing(false)} />
        </Modal>
      ) : null}
    </>
  );
}

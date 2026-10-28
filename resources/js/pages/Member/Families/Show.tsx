import { FamilyForm } from '@/components/forms/FamilyForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { FamilyMark, PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Family, UnitType } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import { Alert, Button, Card, Group, Modal, SimpleGrid, Text, Tooltip } from '@mantine/core';
import { IconHome, IconPencil, IconTrash, IconUserPlus } from '@tabler/icons-react';
import { useState } from 'react';

export default function FamilyShow({
  family,
  unitTypes,
}: {
  family: Family;
  /** For admins, who edit families here */
  unitTypes: UnitType[] | null;
}) {
  const isOwn = usePage().props.project?.family?.id === family.id;
  const [editing, setEditing] = useState(false);
  const remove = async () => {
    const ok = await confirm({
      title: _('Remove the {name} family?', { name: family.name }),
      message: _('Its members leave the project (admins stay admins). This cannot be undone.'),
      confirmLabel: _('Remove'),
      danger: true,
    });
    if (ok) router.delete(route('manage.families.destroy', family.id));
  };

  return (
    <>
      <PageHeader
        title={family.name}
        description={family.unit_type?.name}
        back={{ href: route('families.index'), label: _('Families') }}
        actions={
          <Group gap="sm">
            {isOwn ? (
              <Button
                component={Link}
                href={route('invitations.index')}
                leftSection={<IconUserPlus size={18} />}
              >
                {_('Invite someone')}
              </Button>
            ) : null}
            {unitTypes ? (
              <>
                <Button
                  variant="default"
                  leftSection={<IconPencil size={18} />}
                  onClick={() => setEditing(true)}
                >
                  {_('Edit')}
                </Button>
                <Tooltip
                  label={_('It already received a unit')}
                  disabled={!family.unit}
                  withinPortal
                >
                  <Button
                    variant="default"
                    color="red"
                    c={family.unit ? undefined : 'red'}
                    leftSection={<IconTrash size={18} />}
                    disabled={Boolean(family.unit)}
                    onClick={remove}
                  >
                    {_('Remove')}
                  </Button>
                </Tooltip>
              </>
            ) : null}
          </Group>
        }
      />
      <Group mb="lg">
        <FamilyMark name={family.name} size={64} />
      </Group>
      {family.unit ? (
        <Alert color="green" radius="lg" icon={<IconHome size={20} />} mb="lg">
          {_('Received unit {unit} in the lottery.', { unit: family.unit.identifier })}
        </Alert>
      ) : null}
      <SimpleGrid cols={{ base: 1, sm: 2 }}>
        {family.members?.map((person) => (
          <Card
            key={person.id}
            component={Link}
            href={route('people.index', { person: person.id })}
            p="sm"
            className="ds-interactive"
          >
            <Group>
              <PersonAvatar name={person.name} src={person.avatar} size={44} />
              <Text fw={650}>{person.name}</Text>
            </Group>
          </Card>
        ))}
      </SimpleGrid>
      {!family.members?.length ? (
        <Text ta="center" c="dimmed" py="xl">
          {_('Nobody has joined this family yet.')}
        </Text>
      ) : null}

      {unitTypes ? (
        <Modal opened={editing} onClose={() => setEditing(false)} title={family.name}>
          <FamilyForm family={family} unitTypes={unitTypes} onDone={() => setEditing(false)} />
        </Modal>
      ) : null}
    </>
  );
}

import { FamilyForm } from '@/components/forms/FamilyForm';
import { FamilyInvitationForm } from '@/components/forms/FamilyInvitationForm';
import { FamilyMark, PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Family, UnitType } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import { Alert, Button, Card, Divider, Group, SimpleGrid, Text, Tooltip } from '@mantine/core';
import { IconHome, IconPencil, IconTrash, IconUserPlus } from '@tabler/icons-react';
import { useState } from 'react';

/**
 * A family: its unit type, the unit it received, its members. Its own members and admins
 * invite more people into it; admins edit or remove it.
 */
export function FamilyDetails({
  family,
  unitTypes,
}: {
  family: Family;
  /** For admins, who edit families here */
  unitTypes: UnitType[] | null;
}) {
  const project = usePage().props.project;
  const isOwn = project?.family?.id === family.id;
  const canInvite = isOwn || Boolean(project?.can.manage);
  const [editing, setEditing] = useState(false);
  const [inviting, setInviting] = useState(false);
  const remove = async () => {
    const ok = await confirm({
      title: _('Remove the {name} family?', { name: family.name }),
      message: _('Its members leave the project (admins stay admins). This cannot be undone.'),
      confirmLabel: _('Remove'),
      danger: true,
    });
    if (ok) router.delete(route('manage.families.destroy', family.id));
  };

  // Editing replaces the details in the same dialog (one dialog at a time)
  if (editing && unitTypes)
    return <FamilyForm family={family} unitTypes={unitTypes} onDone={() => setEditing(false)} />;
  if (inviting) return <FamilyInvitationForm family={family} onDone={() => setInviting(false)} />;

  return (
    <>
      <Group mb="lg">
        <FamilyMark name={family.name} size={64} />
        <div>
          {family.unit_type ? (
            <Text fw={650}>{_('Unit type: {type}', { type: family.unit_type.name })}</Text>
          ) : null}
          <Text className="ds-caption">
            {_n('{count} person', '{count} people', family.members?.length ?? 0)}
          </Text>
        </div>
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

      {canInvite || unitTypes ? (
        <>
          <Divider my="lg" />
          <Group gap="sm">
            {canInvite ? (
              <Button leftSection={<IconUserPlus size={18} />} onClick={() => setInviting(true)}>
                {_('Invite a family member')}
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
        </>
      ) : null}
    </>
  );
}

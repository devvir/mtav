import { FamilyForm } from '@/components/forms/FamilyForm';
import { FamilyInvitationForm } from '@/components/forms/FamilyInvitationForm';
import { FamilyMark, PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Family, UnitType } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import {
  Alert,
  Anchor,
  Button,
  Card,
  Divider,
  Group,
  SimpleGrid,
  Stack,
  Text,
  Tooltip,
} from '@mantine/core';
import { IconHome, IconPencil, IconTrash, IconUserPlus } from '@tabler/icons-react';
import { useState } from 'react';

export interface FamilyRequest {
  id: number;
  name: string;
  declined: boolean;
}

/** Who asks to join, for those in the family to answer; a decline stands until they lift it */
function FamilyRequests({ requests }: { requests: FamilyRequest[] }) {
  const answer = (id: number, how: 'accept' | 'decline' | 'undecline') =>
    router.put(route(`family.requests.${how}`, id), {}, { preserveScroll: true });
  const decline = async (request: FamilyRequest) => {
    const ok = await confirm({
      title: _('{name} is not in your family?', { name: request.name }),
      message: _('They cannot ask again, unless someone in your family lifts this.'),
      confirmLabel: _('Decline'),
      danger: true,
    });
    if (ok) answer(request.id, 'decline');
  };

  return (
    <Stack gap="xs" mb="lg" className="family-requests">
      {requests.map((request) => (
        <Card key={request.id} p="sm">
          <Group justify="space-between" gap="sm">
            <Text fw={600}>
              {request.declined
                ? _('{name}: declined', { name: request.name })
                : _('{name} asks to join your family', { name: request.name })}
            </Text>
            {request.declined ? (
              <Button size="xs" variant="default" onClick={() => answer(request.id, 'undecline')}>
                {_('Lift the decline')}
              </Button>
            ) : (
              <Group gap="xs">
                <Button size="xs" onClick={() => answer(request.id, 'accept')}>
                  {_('Accept')}
                </Button>
                <Button size="xs" variant="default" onClick={() => void decline(request)}>
                  {_('Decline')}
                </Button>
              </Group>
            )}
          </Group>
        </Card>
      ))}
    </Stack>
  );
}

/**
 * A family: its unit type, the unit it received, its members. Its own members and admins
 * invite more people into it; admins edit or remove it.
 */
export function FamilyDetails({
  family,
  unitTypes,
  requests,
}: {
  family: Family;
  /** For admins, who edit families here */
  unitTypes: UnitType[] | null;
  /** For those in it, who answer them */
  requests?: FamilyRequest[] | null;
}) {
  const project = usePage().props.project;
  const isOwn = project?.family?.id === family.id;
  const canInvite = isOwn || Boolean(project?.can.manage);
  const [editing, setEditing] = useState(false);
  const [inviting, setInviting] = useState(false);
  /** Why it cannot be removed, if so */
  const notRemovable = family.unit
    ? _('It already received a unit')
    : family.members?.length
      ? _('It still has people: move or remove them first')
      : null;
  const remove = async () => {
    const ok = await confirm({
      title: _('Remove the {name} family?', { name: family.name }),
      message: _('Invitations waiting to be accepted are withdrawn. It stays archived.'),
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
      {requests?.length ? <FamilyRequests requests={requests} /> : null}
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
          <Group gap="sm" justify="flex-end">
            {canInvite ? (
              <Button leftSection={<IconUserPlus size={18} />} onClick={() => setInviting(true)}>
                {/* One's own family is family; anyone else's, a family one adds people to */}
                {isOwn ? _('Invite a family member') : _('Add a new member to this family')}
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
                  label={notRemovable ?? ''}
                  disabled={!notRemovable}
                  events={{ hover: true, focus: true, touch: false }}
                  withinPortal
                >
                  <Button
                    variant="default"
                    color="red"
                    c={notRemovable ? undefined : 'red'}
                    leftSection={<IconTrash size={18} />}
                    // Not `disabled`: it stays reachable, to say why
                    data-disabled={notRemovable ? true : undefined}
                    aria-disabled={Boolean(notRemovable)}
                    onClick={notRemovable ? undefined : remove}
                  >
                    {_('Remove')}
                  </Button>
                </Tooltip>
              </>
            ) : null}
          </Group>
        </>
      ) : null}
      {isOwn ? (
        <Anchor
          component={Link}
          href={route('family.create')}
          size="xs"
          c="dimmed"
          display="block"
          mt="md"
          className="family-wrong"
        >
          {_('Wrong family?')}
        </Anchor>
      ) : null}
    </>
  );
}

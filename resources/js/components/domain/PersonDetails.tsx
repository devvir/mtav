import { DateTime } from '@/components/feedback/DateTime';
import { MembershipForm } from '@/components/forms/MembershipForm';
import { PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { toast } from '@/lib/toasts';
import { _ } from '@/lib/translations';
import type { Person, Ref } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import { Anchor, Badge, Button, Divider, Group, Stack, Text, Tooltip } from '@mantine/core';
import {
  IconId,
  IconLogout,
  IconMail,
  IconPhone,
  IconUserCog,
  IconUserDown,
  IconUserMinus,
} from '@tabler/icons-react';
import { useState } from 'react';

/**
 * Someone in the project: their family and role, about them, how to reach them. Their own
 * details lead to their profile or out of the project, and an admin gives the role up there;
 * admins change people's role or family, or remove them. Admins are peers: only a superadmin
 * takes the role from one, or removes one.
 */
/** Why the server refused: nothing on the page holds a field for it */
const showRefusal = (errors: Record<string, string>) =>
  toast({ tone: 'error', message: Object.values(errors)[0] ?? '' });

export function PersonDetails({
  person,
  families,
}: {
  person: Person;
  /** For admins, who change people's role and family here */
  families: Ref[] | null;
}) {
  const { props } = usePage();
  const isMe = props.auth.user?.id === person.id;
  const isSuperadmin = props.auth.user?.is_superadmin ?? false;
  const [editing, setEditing] = useState(false);

  const remove = async () => {
    const ok = await confirm({
      title: _('Remove {name} from the project?', { name: person.name }),
      message: _('Their account stays; they just stop taking part in this project.'),
      confirmLabel: _('Remove'),
      danger: true,
    });
    if (ok) router.delete(route('manage.people.destroy', person.id), { onError: showRefusal });
  };

  const leave = async () => {
    const project = props.project!.name;
    const ok = await confirm({
      title: _('Leave {project}?', { project }),
      message: _('You will stop seeing the project. Someone has to invite you again to come back.'),
      confirmLabel: _('Leave'),
      danger: true,
    });
    if (ok) router.delete(route('membership.leave'), { onError: showRefusal });
  };

  // Giving the role up: they stay in their family, or as a guest; with neither, they leave
  const renounce = async () => {
    const project = props.project!;
    const leaves = !person.family && !project.allows_guests;
    const ok = await confirm({
      title: _('Stop being an admin of {project}?', { project: project.name }),
      message: person.family
        ? _('You stay in the project, in the {family} family.', { family: person.family.name })
        : leaves
          ? _(
              'You have no family here and the project takes no guests, so you would leave the project. Someone has to invite you again to come back.',
            )
          : _('You stay in the project, as a guest.'),
      confirmLabel: leaves ? _('Leave') : _('Give the role up'),
      danger: true,
    });
    if (ok)
      router.delete(route('membership.renounce'), {
        data: { leave: leaves },
        onError: showRefusal,
      });
  };

  // Editing replaces the details in the same dialog (one dialog at a time)
  if (editing && families)
    return <MembershipForm person={person} families={families} onDone={() => setEditing(false)} />;

  return (
    <>
      <div>
        <Group gap="lg">
          <PersonAvatar
            name={person.name}
            src={person.avatar}
            size={72}
            expandable={_('See the photo of {name}', { name: person.name })}
          />
          <Stack gap={4}>
            <Group gap="xs">
              {person.family ? (
                <Anchor
                  component={Link}
                  href={route('families.index', { family: person.family.id })}
                  fw={650}
                >
                  {_('{family} family', { family: person.family.name })}
                </Anchor>
              ) : null}
              {person.is_admin ? <Badge color="blue">{_('Admin')}</Badge> : null}
              {person.is_guest ? <Badge color="gray">{_('Guest')}</Badge> : null}
            </Group>
            {person.joined_at ? (
              <Text className="ds-caption">
                {_('Joined')} <DateTime value={person.joined_at} time={false} />
              </Text>
            ) : null}
          </Stack>
        </Group>
        {person.about ? (
          <Text mt="lg" style={{ whiteSpace: 'pre-line' }}>
            {person.about}
          </Text>
        ) : null}
        {person.email || person.phone || person.legal_id ? (
          <>
            <Divider my="lg" />
            <Stack gap="xs">
              {person.email ? (
                <Group gap="xs">
                  <IconMail size={18} />
                  <Anchor href={`mailto:${person.email}`}>{person.email}</Anchor>
                </Group>
              ) : null}
              {person.phone ? (
                <Group gap="xs">
                  <IconPhone size={18} />
                  <Anchor href={`tel:${person.phone}`}>{person.phone}</Anchor>
                </Group>
              ) : null}
              {person.legal_id ? (
                <Group gap="xs">
                  <IconId size={18} aria-label={_('ID number')} />
                  <Text>{person.legal_id}</Text>
                </Group>
              ) : null}
            </Stack>
          </>
        ) : null}
        {isMe ? (
          <>
            <Divider my="lg" />
            <Group justify="flex-end">
              <Button variant="default" component={Link} href={route('profile.edit')}>
                {_('Edit your profile')}
              </Button>
              {person.is_admin ? (
                <Tooltip
                  label={_('You are the only admin: make someone else an admin first.')}
                  disabled={props.project?.can.renounce}
                  events={{ hover: true, focus: true, touch: true }}
                  withinPortal
                >
                  <Button
                    variant="subtle"
                    leftSection={<IconUserDown size={18} />}
                    onClick={renounce}
                    data-disabled={!props.project?.can.renounce || undefined}
                    aria-disabled={!props.project?.can.renounce || undefined}
                  >
                    {_('Give the admin role up')}
                  </Button>
                </Tooltip>
              ) : null}
              <Button
                variant="subtle"
                color="red"
                leftSection={<IconLogout size={18} />}
                onClick={leave}
              >
                {_('Leave the project')}
              </Button>
            </Group>
          </>
        ) : null}
      </div>
      {families ? (
        <>
          <Divider my="lg" />
          <Group gap="sm" justify="flex-end">
            <Button
              variant="default"
              leftSection={<IconUserCog size={18} />}
              onClick={() => setEditing(true)}
            >
              {_('Change role or family')}
            </Button>
            {!isMe && (!person.is_admin || isSuperadmin) ? (
              <Button
                variant="default"
                color="red"
                c="red"
                leftSection={<IconUserMinus size={18} />}
                onClick={remove}
              >
                {_('Remove from the project')}
              </Button>
            ) : null}
          </Group>
        </>
      ) : null}
    </>
  );
}

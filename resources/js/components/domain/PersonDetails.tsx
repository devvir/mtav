import { DateTime } from '@/components/feedback/DateTime';
import { MembershipForm } from '@/components/forms/MembershipForm';
import { PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Person, Ref } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import { Anchor, Badge, Button, Divider, Group, Stack, Text } from '@mantine/core';
import {
  IconId,
  IconLogout,
  IconMail,
  IconPhone,
  IconUserCog,
  IconUserMinus,
} from '@tabler/icons-react';
import { useState } from 'react';

/**
 * Someone in the project: their family and role, about them, how to reach them. Their own
 * details lead to their profile or out of the project; admins change their role or family,
 * or remove them.
 */
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
  const [editing, setEditing] = useState(false);

  const remove = async () => {
    const ok = await confirm({
      title: _('Remove {name} from the project?', { name: person.name }),
      message: _('Their account stays; they just stop taking part in this project.'),
      confirmLabel: _('Remove'),
      danger: true,
    });
    if (ok) router.delete(route('manage.people.destroy', person.id));
  };

  const leave = async () => {
    const project = props.project!.name;
    const ok = await confirm({
      title: _('Leave {project}?', { project }),
      message: _('You will stop seeing the project. Someone has to invite you again to come back.'),
      confirmLabel: _('Leave'),
      danger: true,
    });
    if (ok) router.delete(route('membership.leave'));
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
            <Group>
              <Button variant="default" component={Link} href={route('profile.edit')}>
                {_('Edit your profile')}
              </Button>
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
          <Group gap="sm">
            <Button
              variant="default"
              leftSection={<IconUserCog size={18} />}
              onClick={() => setEditing(true)}
            >
              {_('Change role or family')}
            </Button>
            {!isMe ? (
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

import { DateTime } from '@/components/feedback/DateTime';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Person } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import { Anchor, Badge, Button, Card, Divider, Group, Stack, Text } from '@mantine/core';
import { IconLogout, IconMail, IconPhone } from '@tabler/icons-react';

export default function PersonShow({ person }: { person: Person }) {
  const { props } = usePage();
  const isMe = props.auth.user?.id === person.id;

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

  return (
    <>
      <PageHeader title={person.name} back={{ href: route('people.index'), label: _('People') }} />
      <Card p="xl" maw={720}>
        <Group gap="lg">
          <PersonAvatar name={person.name} src={person.avatar} size={72} />
          <Stack gap={4}>
            <Group gap="xs">
              {person.family ? (
                <Anchor component={Link} href={route('families.show', person.family.id)} fw={650}>
                  {_('{family} family', { family: person.family.name })}
                </Anchor>
              ) : null}
              {person.is_admin ? <Badge color="blue">{_('Admin')}</Badge> : null}
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
        {person.email || person.phone ? (
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
      </Card>
    </>
  );
}

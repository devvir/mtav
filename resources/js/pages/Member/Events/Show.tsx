import { DateTime } from '@/components/feedback/DateTime';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar } from '@/design';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { ProjectEvent } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { Anchor, Badge, Button, Card, Group, Stack, Text, Title } from '@mantine/core';
import {
  IconCheck,
  IconClock,
  IconMapPin,
  IconPencil,
  IconVideo,
  IconX,
} from '@tabler/icons-react';

export default function EventShow({ event }: { event: ProjectEvent }) {
  useProjectChanges({ events: ['event'] });
  const rsvp = (attending: boolean) =>
    router.put(route('events.rsvp', event.id), { attending }, { preserveScroll: true });

  return (
    <>
      <PageHeader
        title={event.title}
        back={{ href: route('events.index'), label: _('Events') }}
        actions={
          event.can.update ? (
            <Button
              variant="default"
              component={Link}
              href={route('manage.events.edit', event.id)}
              leftSection={<IconPencil size={18} />}
            >
              {_('Edit')}
            </Button>
          ) : null
        }
      />
      <div className="with-aside">
        <Card p="xl">
          <Stack gap={8} mb="lg" c="dimmed">
            {event.start_date ? (
              <Group gap="xs">
                <IconClock size={20} /> <DateTime value={event.start_date} />
                {event.end_date ? (
                  <>
                    – <DateTime value={event.end_date} />
                  </>
                ) : null}
                {event.status === 'ongoing' ? (
                  <Badge color="green">{_('Happening now')}</Badge>
                ) : null}
              </Group>
            ) : null}
            {event.location ? (
              <Group gap="xs">
                {event.type === 'online' ? (
                  <>
                    <IconVideo size={20} />
                    <Anchor href={event.location} target="_blank" rel="noopener" fw={600}>
                      {_('Join online')}
                    </Anchor>
                  </>
                ) : (
                  <>
                    <IconMapPin size={20} /> {event.location}
                  </>
                )}
              </Group>
            ) : null}
          </Stack>
          <Text style={{ whiteSpace: 'pre-line' }}>{event.description}</Text>
          {event.creator ? (
            <Text className="ds-caption" mt="lg">
              {_('Posted by {name}', { name: event.creator })}
            </Text>
          ) : null}
        </Card>

        {event.allows_rsvp ? (
          <Card p="lg">
            {event.can.rsvp && event.status !== 'completed' ? (
              <>
                <Title order={2} fz="md">
                  {_('Will you go?')}
                </Title>
                <Group grow mt="sm" mb="lg">
                  <Button
                    variant={event.attending === true ? 'filled' : 'default'}
                    aria-pressed={event.attending === true}
                    leftSection={<IconCheck size={18} />}
                    onClick={() => rsvp(true)}
                  >
                    {_('Yes')}
                  </Button>
                  <Button
                    variant={event.attending === false ? 'light' : 'default'}
                    color={event.attending === false ? 'gray' : undefined}
                    aria-pressed={event.attending === false}
                    leftSection={<IconX size={18} />}
                    onClick={() => rsvp(false)}
                  >
                    {_('No')}
                  </Button>
                </Group>
              </>
            ) : null}
            <Title order={2} fz="md">
              {_('Going ({count})', { count: event.attendees?.length ?? 0 })}
            </Title>
            <Stack gap={8} mt="sm">
              {event.attendees?.map((person) => (
                <Group key={person.id} gap="sm">
                  <PersonAvatar name={person.name} size={28} />
                  <Text size="sm">{person.name}</Text>
                </Group>
              ))}
            </Stack>
          </Card>
        ) : null}
      </div>
    </>
  );
}

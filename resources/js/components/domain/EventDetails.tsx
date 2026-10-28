import { DateTime } from '@/components/feedback/DateTime';
import { EventForm } from '@/components/forms/EventForm';
import { PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { ProjectEvent } from '@/types/models';
import { router } from '@inertiajs/react';
import {
  Anchor,
  Badge,
  Button,
  Card,
  Divider,
  Group,
  Modal,
  Stack,
  Text,
  Title,
} from '@mantine/core';
import {
  IconCheck,
  IconClock,
  IconMapPin,
  IconPencil,
  IconTrash,
  IconVideo,
  IconX,
} from '@tabler/icons-react';
import { useState } from 'react';

/**
 * An event: when and where, what it is about, and who goes (answering, where it asks);
 * admins edit or cancel it.
 */
export function EventDetails({ event }: { event: ProjectEvent }) {
  const [editing, setEditing] = useState(false);
  const cancel = async () => {
    const ok = await confirm({
      title: _('Cancel {title}?', { title: event.title }),
      message: _('The event is removed for everyone.'),
      confirmLabel: _('Cancel the event'),
      cancelLabel: _('Keep it'),
      danger: true,
    });
    if (ok) router.delete(route('manage.events.destroy', event.id));
  };
  const rsvp = (attending: boolean) =>
    router.put(route('events.rsvp', event.id), { attending }, { preserveScroll: true });

  return (
    <>
      <Stack gap="lg">
        <div>
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
        </div>

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
      </Stack>

      {event.can.update ? (
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
            {event.can.delete ? (
              <Button
                variant="default"
                color="red"
                c="red"
                leftSection={<IconTrash size={18} />}
                onClick={cancel}
              >
                {_('Cancel the event')}
              </Button>
            ) : null}
          </Group>
          <Modal opened={editing} onClose={() => setEditing(false)} title={event.title} size="lg">
            <EventForm event={event} onDone={() => setEditing(false)} />
          </Modal>
        </>
      ) : null}
    </>
  );
}

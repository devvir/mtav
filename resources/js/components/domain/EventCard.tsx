import { DateTime } from '@/components/feedback/DateTime';
import { route } from '@/lib/route';
import { _, languageTag } from '@/lib/translations';
import type { ProjectEvent } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { Anchor, Badge, Button, Card, Group, Stack } from '@mantine/core';
import { IconCalendarQuestion, IconCheck, IconMapPin, IconVideo, IconX } from '@tabler/icons-react';

/** An event in a list: when, where, and one-tap attendance when it asks for it */
export function EventCard({ event, href }: { event: ProjectEvent; href?: string }) {
  const rsvp = (attending: boolean) =>
    router.put(
      route('events.rsvp', event.id),
      { attending },
      { preserveScroll: true, only: ['events', 'event', 'flash'] },
    );
  const date = event.start_date ? new Date(event.start_date) : null;

  return (
    <Card p="md" className="event-card">
      <Group align="flex-start" gap="md" wrap="nowrap">
        <div className="event-date" aria-hidden>
          {date ? (
            <>
              <span className="event-month">
                {date.toLocaleString(languageTag(), { month: 'short' })}
              </span>
              <span className="event-day">{date.getDate()}</span>
            </>
          ) : (
            <IconCalendarQuestion size={26} stroke={1.6} className="event-undated" />
          )}
        </div>
        <div style={{ flex: 1, minWidth: 0 }}>
          <Group gap="xs">
            <Anchor
              component={Link}
              href={href ?? route('events.show', event.id)}
              fw={700}
              fz="lg"
              c="var(--mtav-text)"
            >
              {event.title}
            </Anchor>
            {!event.is_published ? <Badge color="yellow">{_('Draft')}</Badge> : null}
            {event.status === 'ongoing' ? <Badge color="green">{_('Happening now')}</Badge> : null}
            {event.status === 'completed' ? <Badge color="gray">{_('Past')}</Badge> : null}
          </Group>
          <Group gap="md" mt={4} className="ds-caption">
            {event.start_date ? (
              <DateTime value={event.start_date} />
            ) : (
              <span>{_('No date yet')}</span>
            )}
            {event.location ? (
              <Group gap={4} wrap="nowrap" style={{ minWidth: 0 }}>
                {event.type === 'online' ? <IconVideo size={16} /> : <IconMapPin size={16} />}
                <span className="ds-truncate">
                  {event.type === 'online' ? _('Online') : event.location}
                </span>
              </Group>
            ) : null}
          </Group>
        </div>
        {event.can.rsvp && event.status !== 'completed' ? (
          <Stack gap={6}>
            <Button
              size="xs"
              variant={event.attending === true ? 'filled' : 'default'}
              aria-pressed={event.attending === true}
              leftSection={<IconCheck size={14} />}
              onClick={() => rsvp(true)}
            >
              {_("I'll go")}
            </Button>
            <Button
              size="xs"
              variant={event.attending === false ? 'light' : 'default'}
              color={event.attending === false ? 'gray' : undefined}
              aria-pressed={event.attending === false}
              leftSection={<IconX size={14} />}
              onClick={() => rsvp(false)}
            >
              {_("I can't")}
            </Button>
          </Stack>
        ) : null}
      </Group>
    </Card>
  );
}

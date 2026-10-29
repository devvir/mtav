import { DateTime } from '@/components/feedback/DateTime';
import { route, routeIs } from '@/lib/route';
import { _, languageTag } from '@/lib/translations';
import type { ProjectEvent } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { Anchor, Badge, Button, Card, Group, Stack } from '@mantine/core';
import { IconCalendarQuestion, IconCheck, IconMapPin, IconVideo, IconX } from '@tabler/icons-react';
import { useState } from 'react';

/** The current query (the tab, a search), with this event's details */
const detailsQuery = (id: number) => ({
  ...Object.fromEntries(new URLSearchParams(window.location.search)),
  event: id,
});

/**
 * An event in a list: when, where, and one-tap attendance when it asks for it (shown at
 * once, back as it was should the server refuse it)
 */
export function EventCard({ event, href }: { event: ProjectEvent; href?: string }) {
  const detailsHere = routeIs('events.index');
  const [attending, setAttending] = useState(event.attending);
  const [given, setGiven] = useState(event.attending);
  if (given !== event.attending) {
    setGiven(event.attending);
    setAttending(event.attending);
  }
  const rsvp = (going: boolean) => {
    const before = attending;
    let done = false;
    setAttending(going);
    router.put(
      route('events.rsvp', event.id),
      { attending: going },
      {
        preserveScroll: true,
        only: ['events', 'event', 'flash'],
        // Refused, cut off or cancelled: back as it was
        onSuccess: () => {
          done = true;
        },
        onFinish: () => !done && setAttending(before),
      },
    );
  };
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
              href={
                href ??
                route('events.index', detailsHere ? detailsQuery(event.id) : { event: event.id })
              }
              // On the events list, its details open over it without reloading the list
              {...(detailsHere
                ? { only: ['event'], preserveState: true, preserveScroll: true }
                : {})}
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
              variant={attending === true ? 'filled' : 'default'}
              aria-pressed={attending === true}
              leftSection={<IconCheck size={14} />}
              onClick={() => rsvp(true)}
            >
              {_("I'll go")}
            </Button>
            <Button
              size="xs"
              variant={attending === false ? 'light' : 'default'}
              color={attending === false ? 'gray' : undefined}
              aria-pressed={attending === false}
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

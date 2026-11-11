import { DateTime } from '@/components/feedback/DateTime';
import { detailsQuery } from '@/lib/listFilters';
import { route, routeIs } from '@/lib/route';
import { useRsvp } from '@/lib/rsvp';
import { _, languageTag } from '@/lib/translations';
import type { ProjectEvent } from '@/types/models';
import { Link } from '@inertiajs/react';
import { Anchor, Badge, Card, Group } from '@mantine/core';
import { IconCalendarQuestion, IconMapPin, IconVideo } from '@tabler/icons-react';
import { RsvpButtons } from './RsvpButtons';

/**
 * An event in a list: when, where, and one-tap attendance when it asks for it (shown at
 * once, back as it was should the server refuse it)
 */
export function EventCard({ event, href }: { event: ProjectEvent; href?: string }) {
  const detailsHere = routeIs('events.index');
  const { attending, answer } = useRsvp(event);
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
                route(
                  'events.index',
                  detailsHere ? detailsQuery('event', event.id) : { event: event.id },
                )
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
                <span
                  className="ds-truncate"
                  title={event.type === 'online' ? undefined : event.location}
                >
                  {event.type === 'online' ? _('Online') : event.location}
                </span>
              </Group>
            ) : null}
          </Group>
        </div>
        {event.can.rsvp && event.status !== 'completed' ? (
          <RsvpButtons compact attending={attending} onAnswer={answer} />
        ) : null}
      </Group>
    </Card>
  );
}

import { EventCard } from '@/components/domain/EventCard';
import { MediaGrid } from '@/components/domain/MediaGrid';
import { DateTime } from '@/components/feedback/DateTime';
import { PageHeader } from '@/components/layout/PageHeader';
import { Glyph, Panel, Stat } from '@/design';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media, ProjectEvent } from '@/types/models';
import { Deferred, Link, usePage } from '@inertiajs/react';
import { Anchor, Card, SimpleGrid, Skeleton, Stack, Text, Title } from '@mantine/core';
import {
  IconCalendarEvent,
  IconDice5,
  IconLayoutGrid,
  IconPhoto,
  IconUsers,
  IconUsersGroup,
} from '@tabler/icons-react';

interface Props {
  overview: {
    description: string;
    organization: string | null;
    families_count: number;
    members_count: number;
    units_count: number;
  };
  lottery: {
    start_date: string | null;
    status: string | null;
    unit: { id: number; identifier: string } | null;
  };
  events?: ProjectEvent[];
  media?: Media[];
}

export default function Dashboard({ overview, lottery, events, media }: Props) {
  const project = usePage().props.project!;
  useProjectChanges({
    events: ['events'],
    media: ['media'],
    lottery_runs: ['lottery'],
    families: ['overview'],
    people: ['overview'],
  });

  return (
    <>
      <PageHeader title={project.name} description={overview.description} />

      <SimpleGrid cols={{ base: 1, xs: 3 }} mb="lg">
        <Link href={route('families.index')} className="stat-link">
          <Stat
            label={_('Families')}
            value={overview.families_count}
            icon={IconUsersGroup}
            tone="brand"
          />
        </Link>
        <Link href={route('people.index')} className="stat-link">
          <Stat label={_('People')} value={overview.members_count} icon={IconUsers} tone="info" />
        </Link>
        <Link href={route('units.index')} className="stat-link">
          <Stat
            label={_('Units')}
            value={overview.units_count}
            icon={IconLayoutGrid}
            tone="accent"
          />
        </Link>
      </SimpleGrid>

      <div className="dashboard-top">
        {/* The lottery, the heart of the project */}
        <Card
          component={Link}
          href={route('lottery.show')}
          p="lg"
          className="ds-interactive lottery-card"
        >
          <Glyph icon={IconDice5} size={44} />
          <Title order={2} fz="lg" mt="sm">
            {_('Lottery')}
          </Title>
          {lottery.status === 'completed' ? (
            lottery.unit ? (
              <Text mt={4}>
                {_('Your family received')}{' '}
                <strong className="text-positive">{lottery.unit.identifier}</strong>.
              </Text>
            ) : (
              <Text c="dimmed" mt={4}>
                {_('The lottery is done. See the results.')}
              </Text>
            )
          ) : lottery.status === 'running' ? (
            <Text c="dimmed" mt={4}>
              {_('The lottery is running right now.')}
            </Text>
          ) : lottery.start_date ? (
            <Text c="dimmed" mt={4}>
              {_('Scheduled for')}{' '}
              <Text component="strong" c="var(--mtav-text)" fw={650}>
                <DateTime value={lottery.start_date} />
              </Text>
              . {project.can.rank ? _("Make sure your family's ranking is how you want it.") : null}
            </Text>
          ) : (
            <Text c="dimmed" mt={4}>
              {_('Not scheduled yet.')}
            </Text>
          )}
        </Card>

        <Panel
          title={_('Coming up')}
          icon={IconCalendarEvent}
          actions={
            <Anchor component={Link} href={route('events.index')} size="sm" fw={600}>
              {_('All events')}
            </Anchor>
          }
        >
          <Deferred data="events" fallback={<Skeleton h={96} radius="lg" />}>
            {events?.length ? (
              <Stack gap="sm">
                {events.map((event) => (
                  <EventCard key={event.id} event={event} />
                ))}
              </Stack>
            ) : (
              <Text c="dimmed" ta="center" py="md">
                {_('No upcoming events.')}
              </Text>
            )}
          </Deferred>
        </Panel>
      </div>

      <Panel
        title={_('Recently shared')}
        icon={IconPhoto}
        tone="accent"
        style={{ marginTop: 24 }}
        actions={
          <Anchor component={Link} href={route('media.index')} size="sm" fw={600}>
            {_('All files')}
          </Anchor>
        }
      >
        <Deferred data="media" fallback={<Skeleton h={160} radius="lg" />}>
          {media?.length ? (
            <MediaGrid media={media} />
          ) : (
            <Text c="dimmed" ta="center" py="md">
              {_('Nothing shared yet.')}
            </Text>
          )}
        </Deferred>
      </Panel>
    </>
  );
}

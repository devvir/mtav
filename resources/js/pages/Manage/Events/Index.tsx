import { EventCard } from '@/components/domain/EventCard';
import { Pagination } from '@/components/feedback/Pagination';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated, ProjectEvent } from '@/types/models';
import { Link } from '@inertiajs/react';
import { Button, Stack } from '@mantine/core';
import { IconCalendarPlus } from '@tabler/icons-react';

export default function ManageEvents({ events }: { events: Paginated<ProjectEvent> }) {
  useProjectChanges({ events: ['events'] });
  const href = (event: ProjectEvent) =>
    event.type === 'lottery' ? route('manage.lottery.show') : route('manage.events.edit', event.id);

  return (
    <>
      <PageHeader
        title={_('Events')}
        description={_('Drafts are visible here only, until published.')}
        actions={
          <Button
            component={Link}
            href={route('manage.events.create')}
            leftSection={<IconCalendarPlus size={18} />}
          >
            {_('Create an event')}
          </Button>
        }
      />
      {events.data.length ? (
        <Stack gap="sm">
          {events.data.map((event) => (
            <EventCard key={event.id} event={event} href={href(event)} />
          ))}
        </Stack>
      ) : (
        <EmptyState icon={IconCalendarPlus} title={_('No events yet')} />
      )}
      <Pagination page={events} />
    </>
  );
}

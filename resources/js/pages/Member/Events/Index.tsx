import { EventCard } from '@/components/domain/EventCard';
import { FilterTabs } from '@/components/feedback/FilterTabs';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { EventForm } from '@/components/forms/EventForm';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated, ProjectEvent } from '@/types/models';
import { usePage } from '@inertiajs/react';
import { Button, Group, Modal, Stack } from '@mantine/core';
import { IconCalendarOff, IconPlus } from '@tabler/icons-react';
import { useState } from 'react';

interface Props {
  events: Paginated<ProjectEvent>;
  filters: { q: string | null; when: 'upcoming' | 'past' };
}

/** Whether the address asks for the new event's form (?new=1, as links to "create" do) */
const askedForNew = () => new URLSearchParams(window.location.search).has('new');

export default function EventsIndex({ events, filters }: Props) {
  useProjectChanges({ events: ['events'] });
  const canManage = usePage().props.project!.can.manage;
  const [creating, setCreating] = useState(() => canManage && askedForNew());
  const close = () => {
    setCreating(false);
    if (askedForNew()) {
      const url = new URL(window.location.href);
      url.searchParams.delete('new');
      window.history.replaceState(window.history.state, '', url);
    }
  };

  return (
    <>
      <PageHeader
        title={_('Events')}
        actions={
          canManage ? (
            <Button leftSection={<IconPlus size={18} />} onClick={() => setCreating(true)}>
              {_('Create an event')}
            </Button>
          ) : null
        }
        help={
          <InfoTip title={_('About events')} tour="member.events">
            {_(
              'Meetings, assemblies and activities, published by the admins of the project. Some ask whether you will go.',
            )}
          </InfoTip>
        }
      />
      <Group justify="space-between" mb="md" gap="sm">
        <FilterTabs
          data-tour="events.tabs"
          items={[
            {
              label: _('Upcoming'),
              href: route('events.index'),
              active: filters.when === 'upcoming',
            },
            {
              label: _('Past'),
              href: route('events.index', { when: 'past' }),
              active: filters.when === 'past',
            },
          ]}
        />
        <QuerySearch value={filters.q} />
      </Group>
      {events.data.length ? (
        <Stack gap="sm" data-tour="events.list">
          {events.data.map((event) => (
            <EventCard key={event.id} event={event} />
          ))}
        </Stack>
      ) : (
        <EmptyState
          icon={IconCalendarOff}
          title={filters.when === 'past' ? _('No past events') : _('No upcoming events')}
        />
      )}
      <Pagination page={events} />

      <Modal opened={creating} onClose={close} title={_('Create an event')} size="lg">
        <EventForm onDone={close} />
      </Modal>
    </>
  );
}

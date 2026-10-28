import { EventCard } from '@/components/domain/EventCard';
import { FilterTabs } from '@/components/feedback/FilterTabs';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { EventForm } from '@/components/forms/EventForm';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { useAddressedDialog } from '@/lib/addressedDialog';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated, ProjectEvent } from '@/types/models';
import { usePage } from '@inertiajs/react';
import { Button, Group, Modal, Stack } from '@mantine/core';
import { IconCalendarOff, IconPlus } from '@tabler/icons-react';

interface Props {
  events: Paginated<ProjectEvent>;
  filters: { q: string | null; when: 'upcoming' | 'past' };
}

export default function EventsIndex({ events, filters }: Props) {
  useProjectChanges({ events: ['events'] });
  const canManage = usePage().props.project!.can.manage;
  const creating = useAddressedDialog('new', canManage);

  return (
    <>
      <PageHeader
        title={_('Events')}
        actions={
          canManage ? (
            <Button leftSection={<IconPlus size={18} />} onClick={creating.open}>
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

      <Modal
        opened={creating.opened}
        onClose={creating.close}
        title={_('Create an event')}
        size="lg"
      >
        <EventForm onDone={creating.close} />
      </Modal>
    </>
  );
}

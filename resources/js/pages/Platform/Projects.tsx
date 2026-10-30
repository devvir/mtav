import { DateTime } from '@/components/feedback/DateTime';
import { FilterTabs } from '@/components/feedback/FilterTabs';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { fileSize, Stat, Status } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _, _n, languageTag } from '@/lib/translations';
import type { Paginated, Project } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import {
  Alert,
  Anchor,
  Button,
  Card,
  Group,
  Modal,
  SimpleGrid,
  Stack,
  Text,
  TextInput,
} from '@mantine/core';
import {
  IconAlertTriangle,
  IconArrowBackUp,
  IconShieldCheck,
  IconTrash,
} from '@tabler/icons-react';
import { useState } from 'react';

type Row = Project & {
  people_count: number;
  creator: { id: number; email: string; name: string } | null;
  events_count: number;
  media_count: number;
  media_bytes: number;
  lottery_runs: number;
  archived_at: string | null;
  archived_by: string | null;
};

interface Props {
  projects: Paginated<Row>;
  counts: { awaiting: number; archived: number };
  filters: { q: string | null; show: string };
}

/** How long it lived, from its start to its archiving, in the largest unit that fits */
function lifetime(project: Row) {
  const days = Math.round(
    (new Date(project.archived_at ?? Date.now()).getTime() -
      new Date(project.created_at).getTime()) /
      86_400_000,
  );
  if (days >= 60) return _n('{count} month', '{count} months', Math.round(days / 30));
  return _n('{count} day', '{count} days', Math.max(days, 1));
}

/**
 * Deleting an archived project for good: what goes with it, before anything is done, and
 * its name typed to confirm (the one place nothing can be undone)
 */
function PurgeDialog({ project, onClose }: { project: Row | null; onClose: () => void }) {
  const [typed, setTyped] = useState('');
  const [deleting, setDeleting] = useState(false);
  const close = () => {
    setTyped('');
    onClose();
  };
  const purge = () =>
    project &&
    router.delete(route('admin.projects.destroy', project.id), {
      data: { confirmation: typed.trim() },
      onStart: () => setDeleting(true),
      onFinish: () => setDeleting(false),
      onSuccess: close,
    });

  return (
    <Modal
      opened={Boolean(project)}
      onClose={close}
      title={_('Delete {project} for good?', { project: project?.name ?? '' })}
      size="lg"
    >
      {project ? (
        <Stack gap="md">
          <Alert color="red" radius="lg" icon={<IconAlertTriangle size={20} />}>
            {_('Everything in it is deleted, its files too. This cannot be undone.')}
          </Alert>
          <SimpleGrid cols={{ base: 2, sm: 3 }} spacing="sm">
            <Stat label={_('People')} value={project.people_count} />
            <Stat label={_('Families')} value={project.families_count ?? 0} />
            <Stat label={_('Units')} value={project.units_count ?? 0} />
            <Stat label={_('Events')} value={project.events_count} />
            <Stat
              label={_('Files')}
              value={project.media_count}
              note={project.media_count ? fileSize(project.media_bytes) : null}
            />
            <Stat label={_('Lottery runs')} value={project.lottery_runs} />
          </SimpleGrid>
          <Text className="ds-caption">
            {_('Active for {time}: started on {start}, archived on {end}{by}.', {
              time: lifetime(project),
              start: new Date(project.created_at).toLocaleDateString(languageTag()),
              end: project.archived_at
                ? new Date(project.archived_at).toLocaleDateString(languageTag())
                : '—',
              by: project.archived_by ? ` ${_('by {name}', { name: project.archived_by })}` : '',
            })}
          </Text>
          <TextInput
            label={_('Type {text} to confirm', { text: project.name })}
            value={typed}
            onChange={(event) => setTyped(event.currentTarget.value)}
            autoComplete="off"
            data-autofocus
          />
          <Group justify="flex-end">
            <Button variant="default" onClick={close}>
              {_('Cancel')}
            </Button>
            <Button
              color="red"
              leftSection={<IconTrash size={18} />}
              disabled={typed.trim() !== project.name}
              loading={deleting}
              onClick={purge}
            >
              {_('Delete for good')}
            </Button>
          </Group>
        </Stack>
      ) : null}
    </Modal>
  );
}

export default function PlatformProjects({ projects, counts, filters }: Props) {
  const [purging, setPurging] = useState<Row | null>(null);
  const restore = async (project: Row) => {
    const ok = await confirm({
      title: _('Restore {project}?', { project: project.name }),
      message: _('It comes back as it was, for everyone in it.'),
      confirmLabel: _('Restore'),
    });
    if (ok) router.put(route('admin.projects.restore', project.id), {}, { preserveScroll: true });
  };

  const approve = async (project: Row) => {
    const ok = await confirm({
      title: _('Approve {project}?', { project: project.name }),
      message: _("You trust its admins: the project's limits are lifted."),
      confirmLabel: _('Approve'),
    });
    if (ok) router.put(route('admin.projects.approve', project.id), {}, { preserveScroll: true });
  };

  const shows = [
    { value: 'all', label: _('All') },
    { value: 'awaiting', label: _('Awaiting approval'), count: counts.awaiting },
    { value: 'unapproved', label: _('Not approved') },
    { value: 'approved', label: _('Approved') },
    { value: 'archived', label: _('Archived'), count: counts.archived },
  ];

  return (
    <>
      <PageHeader
        tour="platform.approvals"
        title={_('Projects')}
        description={_('Every project on the platform.')}
        help={
          <InfoTip title={_('About approvals')} tour="platform.approvals">
            {_(
              'Anyone can start a project. Until a superadmin approves it, it works within limits, to prevent abuse.',
            )}
          </InfoTip>
        }
      />

      <Group justify="space-between" mb="md" gap="sm">
        <FilterTabs
          data-tour="approvals.tabs"
          items={shows.map((item) => ({
            label: item.label,
            count: item.count,
            href: route('admin.projects.index', item.value === 'all' ? {} : { show: item.value }),
            active: filters.show === item.value,
          }))}
        />
        <QuerySearch value={filters.q} />
      </Group>

      <Card p={0} data-tour="approvals.list">
        {projects.data.map((project) => (
          <Group key={project.id} className="row" gap="md" wrap="wrap">
            <div style={{ flex: 1, minWidth: 240 }}>
              <Group gap="sm">
                {project.archived_at ? (
                  <Text fw={700}>{project.name}</Text>
                ) : (
                  <Anchor
                    component={Link}
                    href={route('projects.show', { project: project.id })}
                    fw={700}
                    c="var(--mtav-text)"
                  >
                    {project.name}
                  </Anchor>
                )}
                {project.archived_at ? (
                  <Status tone="neutral" pill>
                    {_('Archived')}
                  </Status>
                ) : project.is_approved ? (
                  <Status tone="positive" pill>
                    {_('Approved')}
                  </Status>
                ) : project.approval_requested_at ? (
                  <Status tone="warning" pill live>
                    {_('Asks for approval')}
                  </Status>
                ) : (
                  <Status tone="neutral" pill>
                    {_('Not approved')}
                  </Status>
                )}
              </Group>
              <Text className="ds-caption" mt={4}>
                {[
                  _n('{count} person', '{count} people', project.people_count),
                  _n('{count} family', '{count} families', project.families_count ?? 0),
                  _n('{count} unit', '{count} units', project.units_count ?? 0),
                ].join(' · ')}{' '}
                · <DateTime value={project.created_at} time={false} />
                {project.creator ? ` · ${project.creator.name} (${project.creator.email})` : null}
              </Text>
              {project.archived_at ? (
                <Text className="ds-caption" mt={2}>
                  {_('Archived')} <DateTime value={project.archived_at} time={false} />
                  {project.archived_by
                    ? ` · ${_('by {name}', { name: project.archived_by })}`
                    : null}
                </Text>
              ) : null}
            </div>
            {project.archived_at ? (
              <Group gap="xs">
                <Button
                  variant="default"
                  leftSection={<IconArrowBackUp size={18} />}
                  onClick={() => restore(project)}
                >
                  {_('Restore')}
                </Button>
                <Button
                  variant="default"
                  c="red"
                  leftSection={<IconTrash size={18} />}
                  onClick={() => setPurging(project)}
                >
                  {_('Delete for good')}
                </Button>
              </Group>
            ) : !project.is_approved ? (
              <Button
                variant="default"
                leftSection={<IconShieldCheck size={18} />}
                onClick={() => approve(project)}
              >
                {_('Approve')}
              </Button>
            ) : null}
          </Group>
        ))}
        {!projects.data.length ? (
          <Text ta="center" c="dimmed" py="xl">
            {_('No projects match.')}
          </Text>
        ) : null}
      </Card>
      <Pagination page={projects} />

      <PurgeDialog project={purging} onClose={() => setPurging(null)} />
    </>
  );
}

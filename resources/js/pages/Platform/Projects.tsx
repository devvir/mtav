import { DateTime } from '@/components/feedback/DateTime';
import { FilterTabs } from '@/components/feedback/FilterTabs';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Status } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Paginated, Project } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { Anchor, Button, Card, Group, Text } from '@mantine/core';
import { IconShieldCheck } from '@tabler/icons-react';

type Row = Project & {
  people_count: number;
  creator: { id: number; email: string; name: string } | null;
};

interface Props {
  projects: Paginated<Row>;
  counts: { awaiting: number };
  filters: { q: string | null; show: string };
}

export default function PlatformProjects({ projects, counts, filters }: Props) {
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
  ];

  return (
    <>
      <PageHeader
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
                <Anchor
                  component={Link}
                  href={route('projects.show', { project: project.id })}
                  fw={700}
                  c="var(--mtav-text)"
                >
                  {project.name}
                </Anchor>
                {project.is_approved ? (
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
            </div>
            {!project.is_approved ? (
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
    </>
  );
}

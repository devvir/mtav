import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { Badge, Button, Card, Group, SimpleGrid, Text, Title } from '@mantine/core';
import { IconLayoutGrid, IconSearch, IconUsers } from '@tabler/icons-react';

interface ListedProject {
  id: number;
  name: string;
  organization: string | null;
  description: string;
  members_count: number;
  units_count: number;
  open_to_join: boolean;
  /** Where the viewer stands with it */
  standing: 'in' | 'can_join' | 'can_ask' | 'asked' | 'declined';
}

/** What the viewer can do about a project, given where they stand */
function ProjectAction({ project }: { project: ListedProject }) {
  const options = { preserveScroll: true };

  switch (project.standing) {
    case 'in':
      return (
        <Button
          component={Link}
          href={route('projects.show', { project: project.id })}
          variant="default"
        >
          {_('Open')}
        </Button>
      );
    case 'can_join':
      return (
        <Button onClick={() => router.post(route('directory.join', { project: project.id }))}>
          {_('Join')}
        </Button>
      );
    case 'can_ask':
      return (
        <Button
          onClick={() => router.post(route('directory.ask', { project: project.id }), {}, options)}
        >
          {_('Ask to join')}
        </Button>
      );
    case 'asked':
      return (
        <Group gap="sm">
          <Text className="ds-caption">{_('Request sent')}</Text>
          <Button
            variant="default"
            onClick={() =>
              router.delete(route('directory.withdraw', { project: project.id }), options)
            }
          >
            {_('Withdraw')}
          </Button>
        </Group>
      );
    case 'declined':
      return <Text className="ds-caption">{_('The admins did not accept your request')}</Text>;
  }
}

/** The projects whose admins list them, for anyone to find and join */
export default function Directory({
  listing,
  filters,
}: {
  /** Not `projects`: that is shared, the person's own */
  listing: Paginated<ListedProject>;
  filters: { q: string | null };
}) {
  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Find a project')} tour="account.directory">
            {_(
              'Projects whose admins list them here. Join at once, or ask them, as each one chooses.',
            )}
          </InfoTip>
        }
        title={_('Find a project')}
        tour="account.directory"
      />
      <div data-tour="directory.search" style={{ marginBottom: 24 }}>
        <QuerySearch value={filters.q} placeholder={_('Search projects')} />
      </div>
      {listing.data.length ? (
        <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} data-tour="directory.list">
          {listing.data.map((project) => (
            <Card key={project.id} p="lg" className="directory-card">
              <Group justify="space-between" align="flex-start" wrap="nowrap">
                <Title order={2} fz="lg">
                  {project.name}
                </Title>
                <Badge color={project.open_to_join ? 'green' : 'gray'} style={{ flexShrink: 0 }}>
                  {project.open_to_join ? _('Open to join') : _('By request')}
                </Badge>
              </Group>
              {project.organization ? (
                <Text className="ds-caption" mt={4}>
                  {project.organization}
                </Text>
              ) : null}
              <Text c="dimmed" lineClamp={3} mt="xs">
                {project.description}
              </Text>
              <Group gap="md" mt="md" className="ds-caption">
                <Group gap={6}>
                  <IconUsers size={16} />
                  {_n('{count} person', '{count} people', project.members_count)}
                </Group>
                <Group gap={6}>
                  <IconLayoutGrid size={16} />
                  {_n('{count} unit', '{count} units', project.units_count)}
                </Group>
              </Group>
              <Group justify="flex-end" mt="auto" pt="md">
                <ProjectAction project={project} />
              </Group>
            </Card>
          ))}
        </SimpleGrid>
      ) : (
        <EmptyState icon={IconSearch} title={_('No projects found')}>
          {filters.q
            ? _('Try other words.')
            : _('No project is listed yet. You can start your own.')}
        </EmptyState>
      )}
      <Pagination page={listing} />
    </>
  );
}

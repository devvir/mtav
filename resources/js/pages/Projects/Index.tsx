import { DateTime } from '@/components/feedback/DateTime';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, Glyph } from '@/design';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Project } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import { Badge, Button, Card, Group, SimpleGrid, Stack, Text, Title } from '@mantine/core';
import {
  IconFolderPlus,
  IconMail,
  IconSearch,
  IconShieldCheck,
  IconUsers,
} from '@tabler/icons-react';
import { useState } from 'react';

interface Props {
  projects: Project[];
  invitations: {
    id: number;
    project: string;
    family: string | null;
    is_admin: boolean;
    inviter: string | null;
    expires_at: string;
  }[];
  canCreate: boolean;
}

/** A list this long gets a search */
const SEARCH_FROM = 10;

export default function ProjectsIndex({ projects, invitations, canCreate }: Props) {
  const { lastProject } = usePage().props;
  const [query, setQuery] = useState('');
  const shown = projects.filter((project) =>
    project.name.toLowerCase().includes(query.trim().toLowerCase()),
  );
  const respond = (id: number, accept: boolean) =>
    router.post(route('invitations.respond', id), { accept });
  const startButton = canCreate ? (
    <Button
      component={Link}
      href={route('projects.create')}
      data-tour="projects.start"
      leftSection={<IconFolderPlus size={18} />}
    >
      {_('Start a project')}
    </Button>
  ) : null;

  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Your projects')} tour="account.projects">
            {_('Every project you take part in, and the invitations waiting for you.')}
          </InfoTip>
        }
        title={_('Your projects')}
        tour="account.projects"
        actions={
          <Group gap="sm">
            <Button
              component={Link}
              href={route('directory.index')}
              variant="default"
              leftSection={<IconSearch size={18} />}
            >
              {_('Find a project')}
            </Button>
            {startButton}
          </Group>
        }
      />

      {invitations.length ? (
        <Stack component="section" gap="sm" mb="xl" data-tour="projects.invitations">
          <Title order={2} fz="lg">
            {_('Invitations waiting for you')}
          </Title>
          {invitations.map((invitation) => (
            <Card key={invitation.id} p="md">
              <Group wrap="wrap" gap="md">
                <Glyph icon={IconMail} tone="info" size={42} />
                <div style={{ flex: 1, minWidth: 200 }}>
                  <Text fw={700}>{invitation.project}</Text>
                  <Text className="ds-caption">
                    {[
                      invitation.is_admin ? _('As an admin') : null,
                      invitation.family
                        ? _('In the {family} family', { family: invitation.family })
                        : null,
                      invitation.inviter ? _('From {name}', { name: invitation.inviter }) : null,
                    ]
                      .filter(Boolean)
                      .join(' · ')}{' '}
                    · {_('Until')} <DateTime value={invitation.expires_at} time={false} />
                  </Text>
                </div>
                <Group gap="xs">
                  <Button variant="default" onClick={() => respond(invitation.id, false)}>
                    {_('Decline')}
                  </Button>
                  <Button onClick={() => respond(invitation.id, true)}>{_('Join')}</Button>
                </Group>
              </Group>
            </Card>
          ))}
        </Stack>
      ) : null}

      {projects.length > SEARCH_FROM ? (
        <div style={{ marginBottom: 16 }}>
          <QuerySearch placeholder={_('Search projects')} onSearch={setQuery} />
        </div>
      ) : null}

      {projects.length ? (
        <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} data-tour="projects.list">
          {shown.map((project) => (
            <Card
              key={project.id}
              aria-current={project.id === lastProject ? 'true' : undefined}
              data-current={project.id === lastProject || undefined}
              component={Link}
              href={route('projects.show', { project: project.id })}
              className="ds-interactive"
              p="lg"
            >
              <Group justify="space-between" align="flex-start" wrap="nowrap">
                <Title order={2} fz="lg">
                  {project.name}
                </Title>
                {project.is_admin ? (
                  <Badge color="blue" leftSection={<IconShieldCheck size={12} />}>
                    {_('Admin')}
                  </Badge>
                ) : null}
              </Group>
              <Text c="dimmed" lineClamp={2} mt="xs">
                {project.description}
              </Text>
              <Group gap="md" mt="md">
                <Group gap={6} className="ds-caption">
                  <IconUsers size={16} />
                  {_n('{count} person', '{count} people', project.members_count ?? 0)}
                </Group>
                {!project.is_approved ? <Badge color="gray">{_('Limited')}</Badge> : null}
                {project.id === lastProject ? <Badge color="green">{_('Current')}</Badge> : null}
              </Group>
            </Card>
          ))}
        </SimpleGrid>
      ) : !invitations.length ? (
        <EmptyState
          icon={IconFolderPlus}
          title={_('You are not in any project yet')}
          action={
            canCreate ? (
              <Button component={Link} href={route('projects.create')}>
                {_('Start a project')}
              </Button>
            ) : null
          }
        >
          {_(
            'When someone invites you, the invitation appears here. You can also find a project to join, or start your own.',
          )}
        </EmptyState>
      ) : null}
    </>
  );
}

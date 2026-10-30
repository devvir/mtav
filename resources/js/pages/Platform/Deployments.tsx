import { DateTime } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Panel, Status, type Tone } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import { Deferred, router, usePage } from '@inertiajs/react';
import {
  Alert,
  Anchor,
  Button,
  Card,
  Checkbox,
  Group,
  Select,
  Skeleton,
  Stack,
  Text,
  TextInput,
} from '@mantine/core';
import {
  IconArrowBackUp,
  IconGitBranch,
  IconRocket,
  IconServer,
  IconTimeline,
} from '@tabler/icons-react';
import { useState } from 'react';

interface Commit {
  sha: string;
  short: string;
  message: string;
  date: string | null;
}

interface Environment {
  name: 'production' | 'staging' | 'dev';
  url: string;
  live:
    | (Commit & { since: string; by: string | null; run_url: string | null; rolled_back: boolean })
    | null;
  behind: number | null;
}

interface Run {
  id: number;
  workflow: string;
  title: string;
  branch: string;
  sha: string;
  status: string;
  conclusion: string | null;
  url: string;
  started_at: string;
  by: string | null;
}

interface Props {
  /** null: GitHub did not answer */
  main?: Commit | null;
  environments?: Environment[] | null;
  branches?: string[] | null;
  runs?: Run[] | null;
}

/** A run, as its state reads */
const runState = (run: Run): [Tone, string] =>
  run.status !== 'completed'
    ? run.status === 'waiting'
      ? ['warning', _('Waiting for approval')]
      : ['info', _('Running')]
    : run.conclusion === 'success'
      ? ['positive', _('Done')]
      : run.conclusion === 'cancelled'
        ? ['neutral', _('Cancelled')]
        : ['negative', _('Failed')];

/**
 * What each environment runs against main, and deploying: dev from any branch, production
 * from main (approved on GitHub), any environment back to its release before. GitHub runs
 * every deploy; the page follows it.
 */
export default function Deployments({ main, environments, branches, runs }: Props) {
  const { errors } = usePage().props as { errors: Record<string, string> };
  const [branch, setBranch] = useState<string | null>('main');
  const [fresh, setFresh] = useState(false);
  const [commit, setCommit] = useState('');
  const [sending, setSending] = useState<string | null>(null);

  const send = (what: string, url: string, data: Record<string, unknown>) =>
    router.post(url, data as never, {
      preserveScroll: true,
      onStart: () => setSending(what),
      onFinish: () => setSending(null),
      // What GitHub now runs, a moment after
      onSuccess: () =>
        window.setTimeout(() => router.reload({ only: ['runs', 'environments'] }), 4000),
    });

  const rollback = async (environment: Environment) => {
    const ok = await confirm({
      title: _('Put {environment} back to its release before?', { environment: environment.name }),
      message: _('The release before comes back at once; its database keeps the newer migrations.'),
      confirmLabel: _('Roll back'),
      danger: true,
    });
    if (ok)
      send(`rollback-${environment.name}`, route('admin.deployments.rollback'), {
        environment: environment.name,
      });
  };

  const unanswered = (
    <Alert color="orange" radius="lg">
      {_('GitHub did not answer. Try again in a moment.')}
    </Alert>
  );

  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Deployments')} tour="platform.deployments">
            {_(
              'What each environment runs, deploying a branch to dev or main to production, and going back to an earlier release.',
            )}
          </InfoTip>
        }
        title={_('Deployments')}
        tour="platform.deployments"
        description={_('What each environment runs, and deploying it.')}
      />
      {errors.deploy ? (
        <Alert color="red" radius="lg" mb="lg">
          {errors.deploy}
        </Alert>
      ) : null}

      <Stack gap="lg">
        <Deferred data={['main', 'environments']} fallback={<Skeleton h={180} radius="lg" />}>
          {environments && main ? (
            <div className="deploy-environments" data-tour="deploy.environments">
              {environments.map((environment) => (
                <Panel
                  key={environment.name}
                  title={environment.name}
                  icon={IconServer}
                  aside={
                    <Anchor href={environment.url} target="_blank" rel="noreferrer">
                      {environment.url.replace('https://', '')}
                    </Anchor>
                  }
                >
                  {environment.live ? (
                    <Stack gap={6}>
                      <Group gap="xs">
                        <Text ff="monospace" fw={700}>
                          {environment.live.short}
                        </Text>
                        {environment.behind === 0 ? (
                          <Status tone="positive">{_('Up to date with main')}</Status>
                        ) : environment.behind ? (
                          <Status tone="warning">
                            {_n(
                              '{count} commit behind main',
                              '{count} commits behind main',
                              environment.behind,
                            )}
                          </Status>
                        ) : null}
                        {environment.live.rolled_back ? (
                          <Status tone="info">{_('Rolled back')}</Status>
                        ) : null}
                      </Group>
                      <Text size="sm" lineClamp={2}>
                        {environment.live.message}
                      </Text>
                      <Text className="ds-caption">
                        {_('Live since')} <DateTime value={environment.live.since} />
                        {environment.live.by ? ` · ${environment.live.by}` : null}
                      </Text>
                    </Stack>
                  ) : (
                    <Text c="dimmed" size="sm">
                      {_('Nothing deployed yet.')}
                    </Text>
                  )}
                  {environment.live ? (
                    <Button
                      mt="md"
                      variant="default"
                      size="xs"
                      leftSection={<IconArrowBackUp size={16} />}
                      loading={sending === `rollback-${environment.name}`}
                      onClick={() => rollback(environment)}
                    >
                      {_('Roll back')}
                    </Button>
                  ) : null}
                </Panel>
              ))}
            </div>
          ) : (
            unanswered
          )}
        </Deferred>

        <div className="two-columns">
          <Panel title={_('Deploy dev')} icon={IconGitBranch} data-tour="deploy.dev">
            <Stack gap="sm">
              <Deferred data="branches" fallback={<Skeleton h={36} />}>
                {branches ? (
                  <Select
                    label={_('Branch')}
                    data={branches}
                    value={branch}
                    onChange={setBranch}
                    searchable
                    allowDeselect={false}
                  />
                ) : (
                  unanswered
                )}
              </Deferred>
              <Checkbox
                label={_('Start from fresh demo data')}
                checked={fresh}
                onChange={(event) => setFresh(event.currentTarget.checked)}
              />
              <Group justify="flex-end">
                <Button
                  leftSection={<IconRocket size={18} />}
                  disabled={!branch}
                  loading={sending === 'dev'}
                  onClick={() => send('dev', route('admin.deployments.dev'), { branch, fresh })}
                >
                  {_('Deploy dev')}
                </Button>
              </Group>
            </Stack>
          </Panel>

          <Panel
            title={_('Deploy production')}
            icon={IconRocket}
            tone="accent"
            data-tour="deploy.production"
          >
            <Stack gap="sm">
              <Text size="sm">
                {_('From main: the release staging runs, unless a commit of main is named.')}
              </Text>
              <TextInput
                label={_('Commit')}
                placeholder={_('The one staging runs')}
                value={commit}
                onChange={(event) => setCommit(event.currentTarget.value.trim())}
                styles={{ input: { fontFamily: 'var(--mantine-font-family-monospace)' } }}
              />
              <Text className="ds-caption">{_('It waits for approval on GitHub.')}</Text>
              <Group justify="flex-end">
                <Button
                  leftSection={<IconRocket size={18} />}
                  loading={sending === 'production'}
                  onClick={() =>
                    send('production', route('admin.deployments.production'), {
                      commit: commit || null,
                    })
                  }
                >
                  {_('Deploy production')}
                </Button>
              </Group>
            </Stack>
          </Panel>
        </div>

        <Panel title={_('Latest runs')} icon={IconTimeline} flush data-tour="deploy.runs">
          <Deferred data="runs" fallback={<Skeleton h={160} />}>
            {runs ? (
              runs.length ? (
                <Card p={0} radius={0} bg="transparent">
                  {runs.map((run) => {
                    const [tone, state] = runState(run);
                    return (
                      <Group key={run.id} className="row" wrap="nowrap" gap="md">
                        <div style={{ flex: 1, minWidth: 0 }}>
                          <Anchor href={run.url} target="_blank" rel="noreferrer" fw={650}>
                            {run.title}
                          </Anchor>
                          <Text className="ds-caption">
                            {run.workflow.replace('.yml', '')} · {run.branch} ·{' '}
                            <Text span ff="monospace" fz="inherit">
                              {run.sha}
                            </Text>{' '}
                            · <DateTime value={run.started_at} />
                            {run.by ? ` · ${run.by}` : null}
                          </Text>
                        </div>
                        <Status tone={tone} live={run.status !== 'completed'}>
                          {state}
                        </Status>
                      </Group>
                    );
                  })}
                </Card>
              ) : (
                <Text c="dimmed" p="md">
                  {_('No runs yet.')}
                </Text>
              )
            ) : (
              unanswered
            )}
          </Deferred>
        </Panel>
      </Stack>
    </>
  );
}

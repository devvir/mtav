import { PageHeader, Panel, Stat, Status } from '@/design';
import { Anchor, Group, Progress, SimpleGrid, Stack, Table, Text } from '@mantine/core';
import {
  IconBrandGithub,
  IconChartBar,
  IconCpu,
  IconDatabase,
  IconGitBranch,
  IconRobot,
  IconServer,
  IconWorld,
} from '@tabler/icons-react';
import type { Readings } from '../api';
import { ago } from '../format';
import { Meter } from '../parts/Meter';
import { Source } from '../parts/Source';

const RUN_TONE = (status: string, conclusion: string | null) =>
  status !== 'completed'
    ? 'info'
    : conclusion === 'success'
      ? 'positive'
      : conclusion === 'skipped' || conclusion === 'cancelled'
        ? 'neutral'
        : 'negative';

/** Everything at a glance: this machine, the sites, the local stacks, the repository and its pipelines */
export function Overview({ readings }: { readings?: Readings }) {
  const host = readings?.host.data;
  const sites = readings?.sites.data ?? [];
  const up = sites.filter((site) => site.status === 200).length;
  const stacks = Object.values(readings?.stacks.data ?? {}).flat();
  const running = stacks.filter((one) => one.state === 'running').length;
  const runs = readings?.pipelines.data ?? [];
  const failing = runs.filter(
    (run) => run.status === 'completed' && run.conclusion === 'failure',
  ).length;

  return (
    <>
      <PageHeader
        title="Overview"
        description="MTAV at a glance: this machine, the sites, the local stacks and the work in the repository."
      />
      <SimpleGrid cols={{ base: 1, sm: 2, lg: 4 }} mb="lg">
        <Stat
          label="Sites answering"
          value={sites.length ? `${up} of ${sites.length}` : '—'}
          icon={IconWorld}
          tone={up === sites.length ? 'positive' : 'warning'}
        />
        <Stat
          label="Local containers"
          value={stacks.length ? `${running} of ${stacks.length}` : '—'}
          icon={IconServer}
          tone="info"
          note="running"
        />
        <Stat
          label="This machine"
          value={host ? `${host.load[0]} / ${host.cores}` : '—'}
          icon={IconCpu}
          tone={host && host.load[0] > host.cores ? 'warning' : 'brand'}
          note="load over its cores"
        />
        <Stat
          label="Pipelines"
          value={runs.length ? `${failing} failed` : '—'}
          icon={IconBrandGithub}
          tone={failing ? 'negative' : 'positive'}
          note="of the latest 30 runs"
        />
      </SimpleGrid>

      <div className="warden-columns">
        <Stack gap="lg">
          <Panel title="Sites" icon={IconWorld} aside="each one's /up">
            <Source reading={readings?.sites}>
              {(list) => (
                <Stack gap={8}>
                  {list.map((site) => (
                    <Group key={site.name} justify="space-between">
                      <Anchor href={site.url} target="_blank" size="sm">
                        {site.name}
                      </Anchor>
                      <Status tone={site.status === 200 ? 'positive' : 'negative'}>
                        {site.status === 200
                          ? `up · ${site.ms} ms`
                          : site.status
                            ? `answers ${site.status}`
                            : (site.error ?? 'down')}
                      </Status>
                    </Group>
                  ))}
                </Stack>
              )}
            </Source>
          </Panel>

          <Panel title="This machine" icon={IconCpu}>
            <Source reading={readings?.host}>
              {(it) => (
                <Stack gap="sm">
                  <Meter label="Memory" space={it.memory} />
                  <Meter label="Disk" space={it.disk} />
                </Stack>
              )}
            </Source>
          </Panel>

          <Panel title="Local stacks" icon={IconServer} aside="Docker, here" flush>
            <Source reading={readings?.stacks}>
              {(all) => (
                <Table verticalSpacing={6} horizontalSpacing="md">
                  <Table.Tbody>
                    {Object.entries(all).flatMap(([stack, containers]) =>
                      containers.map((one) => (
                        <Table.Tr key={one.name}>
                          <Table.Td>
                            <Text size="sm">{one.name.replace(`${stack}-`, '')}</Text>
                            <Text size="xs" c="dimmed">
                              {stack}
                            </Text>
                          </Table.Td>
                          <Table.Td>
                            <Status
                              tone={
                                one.healthy
                                  ? 'positive'
                                  : one.state === 'running'
                                    ? 'warning'
                                    : 'neutral'
                              }
                            >
                              {one.status}
                            </Status>
                          </Table.Td>
                          <Table.Td className="warden-mono" ta="right">
                            {one.memory ?? ''}
                          </Table.Td>
                        </Table.Tr>
                      )),
                    )}
                  </Table.Tbody>
                </Table>
              )}
            </Source>
          </Panel>
        </Stack>

        <Stack gap="lg">
          <Panel
            title="Repository"
            icon={IconGitBranch}
            aside={readings?.repository.data ? `on ${readings.repository.data.branch}` : undefined}
            flush
          >
            <Source reading={readings?.repository}>
              {(repo) => (
                <>
                  <Text size="sm" px="md" pt="sm">
                    {repo.commit}
                    {repo.changed ? (
                      <Text span c="dimmed" size="sm">
                        {' '}
                        · {repo.changed} files changed
                      </Text>
                    ) : null}
                  </Text>
                  <Table verticalSpacing={6} horizontalSpacing="md" mt="xs">
                    <Table.Tbody>
                      {repo.remote
                        .slice()
                        .sort((a, b) => b.at - a.at)
                        .slice(0, 10)
                        .map((branch) => (
                          <Table.Tr key={branch.name}>
                            <Table.Td>
                              <Text size="sm">{branch.name}</Text>
                            </Table.Td>
                            <Table.Td className="warden-mono">{branch.commit}</Table.Td>
                            <Table.Td>
                              <Text size="xs" c="dimmed">
                                {branch.when}
                                {branch.merged && !['main', 'staging', 'dev'].includes(branch.name)
                                  ? ' · in main'
                                  : ''}
                              </Text>
                            </Table.Td>
                          </Table.Tr>
                        ))}
                    </Table.Tbody>
                  </Table>
                </>
              )}
            </Source>
          </Panel>

          <Panel
            title="Pipelines"
            icon={IconBrandGithub}
            aside="the latest of each, on each branch"
            flush
          >
            <Source reading={readings?.pipelines}>
              {(list) => (
                <Table verticalSpacing={6} horizontalSpacing="md">
                  <Table.Tbody>
                    {latestRuns(list)
                      .slice(0, 12)
                      .map((run) => (
                        <Table.Tr key={`${run.url}`}>
                          <Table.Td>
                            <Anchor href={run.url} target="_blank" size="sm">
                              {run.workflow}
                            </Anchor>
                            <Text size="xs" c="dimmed">
                              {run.branch} · {run.commit}
                            </Text>
                          </Table.Td>
                          <Table.Td>
                            <Status
                              tone={RUN_TONE(run.status, run.conclusion)}
                              live={run.status !== 'completed'}
                            >
                              {run.status === 'completed' ? run.conclusion : run.status}
                            </Status>
                          </Table.Td>
                          <Table.Td ta="right">
                            <Text size="xs" c="dimmed">
                              {ago(run.at)}
                            </Text>
                          </Table.Td>
                        </Table.Tr>
                      ))}
                  </Table.Tbody>
                </Table>
              )}
            </Source>
          </Panel>

          <SimpleGrid cols={{ base: 1, md: 2 }}>
            <Panel title="The local app" icon={IconDatabase} aside="its tables">
              <Source reading={readings?.app}>
                {(counts) => (
                  <Stack gap={4}>
                    {Object.entries(counts).map(([table, count]) => (
                      <Group key={table} justify="space-between">
                        <Text size="sm">{table.replaceAll('_', ' ')}</Text>
                        <Text size="sm" className="warden-mono">
                          {count ?? '—'}
                        </Text>
                      </Group>
                    ))}
                  </Stack>
                )}
              </Source>
            </Panel>
            <Panel title="Claude accounts" icon={IconRobot} aside="as their own readings say">
              <Source reading={readings?.claude}>
                {(accounts) =>
                  accounts.length ? (
                    <Stack gap="md">
                      {accounts.map((account) => (
                        <ClaudeAccount
                          key={account.account}
                          account={account}
                          now={readings?.claude.at ?? 0}
                        />
                      ))}
                    </Stack>
                  ) : (
                    <Text size="sm" c="dimmed">
                      No account has a reading yet.
                    </Text>
                  )
                }
              </Source>
            </Panel>
          </SimpleGrid>
        </Stack>
      </div>
      <Text size="xs" c="dimmed" mt="lg">
        <IconChartBar size={12} /> Each part reads on its own pace, and only while this page is
        open.
      </Text>
    </>
  );
}

interface Usage {
  percent?: number;
  resets_at?: number;
}

/** In how long a moment comes, in a few words */
const until = (at: number | undefined, now: number) => {
  if (!at || at * 1000 < now) return null;
  const minutes = Math.round((at * 1000 - now) / 60000);
  return minutes < 60
    ? `${minutes} min`
    : minutes < 2880
      ? `${Math.round(minutes / 60)} h`
      : `${Math.round(minutes / 1440)} d`;
};

/** A Claude account: resting or not, and its five-hour and weekly use, each with when it resets */
function ClaudeAccount({ account, now }: { account: Record<string, unknown>; now: number }) {
  const reading = account.reading as { five_hour?: Usage; weekly?: Usage } | undefined;
  const resting = typeof account.quiet_until === 'number' && account.quiet_until * 1000 > now;
  const line = (label: string, part?: Usage) =>
    part?.percent != null ? (
      <div className="warden-meter">
        <Group justify="space-between" gap="xs" wrap="nowrap">
          <Text size="xs" c="dimmed">
            {label}
          </Text>
          <Text size="xs" className="warden-mono">
            {part.percent}%
            {until(part.resets_at, now) ? ` · resets in ${until(part.resets_at, now)}` : ''}
          </Text>
        </Group>
        <Progress
          value={part.percent}
          size="sm"
          radius="xl"
          color={part.percent > 90 ? 'red' : part.percent > 70 ? 'yellow' : 'brand'}
        />
      </div>
    ) : null;
  return (
    <div>
      <Group justify="space-between" mb={4} wrap="nowrap">
        <Text size="sm" fw={650} className="ds-truncate">
          {String(account.account)}
        </Text>
        {resting ? <Status tone="warning">resting</Status> : null}
      </Group>
      {line('5 hours', reading?.five_hour) ?? null}
      {line('Week', reading?.weekly) ?? null}
      {!reading?.five_hour && !reading?.weekly ? (
        <Text size="xs" c="dimmed">
          No reading yet
        </Text>
      ) : null}
    </div>
  );
}

/** The latest run of each workflow on each branch; Dependabot's runs left out */
const latestRuns = <T extends { workflow: string; branch: string }>(runs: T[]) => {
  const seen = new Set<string>();
  return runs.filter((run) => {
    const key = `${run.workflow} ${run.branch}`;
    if (
      run.branch.startsWith('dependabot/') ||
      run.workflow === 'Dependabot Updates' ||
      seen.has(key)
    )
      return false;
    seen.add(key);
    return true;
  });
};

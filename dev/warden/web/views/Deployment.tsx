import { PageHeader, Panel, Stat, Status, type Tone } from '@/design';
import {
  Alert,
  Badge,
  Button,
  Group,
  Menu,
  Modal,
  Select,
  SimpleGrid,
  Stack,
  Table,
  Text,
  Tooltip,
} from '@mantine/core';
import {
  IconArrowBackUp,
  IconChevronDown,
  IconCoin,
  IconDatabaseExport,
  IconPlayerPlay,
  IconPlayerStop,
  IconRefresh,
  IconRocket,
  IconServer2,
  IconStack2,
  IconTopologyStar3,
} from '@tabler/icons-react';
import { useState, type ReactNode } from 'react';
import { act, type CloudServer, type Job, type Readings } from '../api';
import { ago, bytes, money, short } from '../format';
import { JobOutput } from '../parts/JobOutput';
import { Meter } from '../parts/Meter';
import { Source } from '../parts/Source';

const ENVIRONMENTS = ['production', 'sorteo', 'staging', 'dev'] as const;
type Environment = (typeof ENVIRONMENTS)[number];
/** The branch each environment follows */
const BRANCH: Record<Environment, string> = {
  production: 'main',
  sorteo: 'main',
  staging: 'staging',
  dev: 'dev',
};

const SERVER_TONE: Record<CloudServer['state'], Tone> = {
  running: 'positive',
  pending: 'info',
  stopping: 'info',
  stopped: 'neutral',
};

/** Where an environment runs and what: on an AWS server, still on the server before, or nowhere (its names show the static page) */
function whereIs(environment: Environment, readings?: Readings) {
  const server = readings?.cloud.data?.find((one) => one.environments.includes(environment));
  if (server) {
    return {
      where: `AWS · ${server.name}`,
      server,
      live: server.live?.[environment] ?? null,
      previous: server.previous?.[environment] ?? null,
    };
  }
  const before = readings?.server.data?.releases?.[environment];
  if (before?.length) {
    const live = before.find((one) => one.live);
    return {
      where: 'The server before AWS',
      server: null,
      live: live ? { slot: live.slot, commit: live.commit } : null,
      previous: null,
    };
  }
  return { where: 'Off: the static page', server: null, live: null, previous: null };
}

/** A button that asks before doing it */
function Confirmed({
  label,
  question,
  onConfirm,
  children,
  color,
  disabled,
}: {
  label: string;
  question: ReactNode;
  onConfirm: () => void;
  children: ReactNode;
  color?: string;
  disabled?: boolean;
}) {
  const [open, setOpen] = useState(false);
  return (
    <>
      <span onClick={() => !disabled && setOpen(true)}>{children}</span>
      <Modal opened={open} onClose={() => setOpen(false)} title={label} centered>
        <Text size="sm">{question}</Text>
        <Group justify="flex-end" mt="lg">
          <Button variant="default" onClick={() => setOpen(false)}>
            Cancel
          </Button>
          <Button
            color={color}
            onClick={() => {
              setOpen(false);
              onConfirm();
            }}
          >
            {label}
          </Button>
        </Group>
      </Modal>
    </>
  );
}

/** Deploying and running MTAV: its environments, AWS's servers, the infrastructure, money and backups, what it is doing */
export function Deployment({ readings, jobs }: { readings?: Readings; jobs: Job[] }) {
  const [shown, setShown] = useState<string | null>(null);
  const [problem, setProblem] = useState<string | null>(null);
  const [branch, setBranch] = useState<string | null>('dev');

  const latest = shown ?? jobs[0]?.id ?? null;
  const running = (kind: string) =>
    jobs.some((job) => job.kind === kind && job.state === 'running');
  const start = async (action: string, params: Record<string, unknown> = {}) => {
    setProblem(null);
    try {
      setShown(await act(action, params));
    } catch (error) {
      setProblem(String((error as Error).message));
    }
  };

  const cloud = readings?.cloud.data ?? [];
  const heads = Object.fromEntries(
    (readings?.repository.data?.remote ?? []).map((one) => [one.name, one.commit]),
  );
  const branches = (readings?.repository.data?.remote ?? []).map((one) => one.name).sort();
  const sites = Object.fromEntries((readings?.sites.data ?? []).map((one) => [one.name, one]));
  const onAws = ENVIRONMENTS.filter((env) =>
    cloud.some((server) => server.environments.includes(env)),
  ).length;
  const plan = readings?.money.data?.plan;
  const month = readings?.money.data?.month;

  // The infrastructure: the latest check, and whether it is still the one to apply
  const lastCheck = jobs.find((job) => job.kind === 'check');
  const lastApply = jobs.find((job) => job.kind === 'apply');
  const checkReadyToApply =
    lastCheck?.state === 'done' && (!lastApply || lastApply.started < lastCheck.started);

  return (
    <>
      <PageHeader
        title="Deployment"
        description="Where each environment runs and what, AWS's servers, the infrastructure, the money and the backups."
      />
      {problem ? (
        <Alert color="red" variant="light" mb="md" withCloseButton onClose={() => setProblem(null)}>
          {problem}
        </Alert>
      ) : null}

      <SimpleGrid cols={{ base: 1, sm: 2, lg: 4 }} mb="lg">
        <Stat
          label="Environments on AWS"
          value={`${onAws} of ${ENVIRONMENTS.length}`}
          icon={IconStack2}
          tone="brand"
        />
        <Stat
          label="Servers running"
          value={
            readings?.cloud.data
              ? `${cloud.filter((one) => one.state === 'running').length} of ${cloud.length}`
              : '—'
          }
          icon={IconServer2}
          tone="info"
        />
        <Stat
          label="Credits left"
          value={plan?.credits != null ? money(plan.credits) : '—'}
          icon={IconCoin}
          tone="positive"
          note={
            plan
              ? `${plan.type.toLowerCase()} plan, until ${new Date(plan.until).toLocaleDateString()}`
              : undefined
          }
        />
        <Stat
          label="This month"
          value={month ? money(month.spent) : '—'}
          icon={IconCoin}
          tone={month && month.spent > month.limit ? 'negative' : 'accent'}
          note={
            month
              ? `of a ${money(month.limit)} budget${month.forecast ? `, ${money(month.forecast)} forecast` : ''}`
              : undefined
          }
        />
      </SimpleGrid>

      <Stack gap="lg">
        <Panel
          title="Environments"
          icon={IconStack2}
          aside="each deploys through its GitHub workflow"
          flush
        >
          <Table verticalSpacing="sm" horizontalSpacing="md">
            <Table.Thead>
              <Table.Tr>
                <Table.Th>Environment</Table.Th>
                <Table.Th>Where</Table.Th>
                <Table.Th>Live</Table.Th>
                <Table.Th>Its branch</Table.Th>
                <Table.Th>Site</Table.Th>
                <Table.Th />
              </Table.Tr>
            </Table.Thead>
            <Table.Tbody>
              {ENVIRONMENTS.map((environment) => {
                const it = whereIs(environment, readings);
                const head = heads[BRANCH[environment]];
                const behind = Boolean(it.live?.commit && head && !it.live.commit.startsWith(head));
                const site = sites[environment];
                return (
                  <Table.Tr key={environment}>
                    <Table.Td>
                      <Text fw={650} tt="capitalize">
                        {environment}
                      </Text>
                    </Table.Td>
                    <Table.Td>
                      <Text size="sm">{it.where}</Text>
                      {site?.leads === 'before' && it.server ? (
                        <Text size="xs" c="yellow">
                          its name still leads to the server before AWS
                        </Text>
                      ) : null}
                    </Table.Td>
                    <Table.Td>
                      {it.live ? (
                        <Tooltip
                          label={`slot ${it.live.slot}${it.previous ? `, before: ${short(it.previous.commit)}` : ''}`}
                        >
                          <Text size="sm" className="warden-mono">
                            {short(it.live.commit)}
                          </Text>
                        </Tooltip>
                      ) : (
                        <Text size="sm" c="dimmed">
                          nothing
                        </Text>
                      )}
                    </Table.Td>
                    <Table.Td>
                      <Group gap={6}>
                        <Text size="sm">{BRANCH[environment]}</Text>
                        <Text size="xs" className="warden-mono" c="dimmed">
                          {head ?? ''}
                        </Text>
                        {behind ? (
                          <Badge color="yellow" variant="light">
                            behind
                          </Badge>
                        ) : null}
                      </Group>
                    </Table.Td>
                    <Table.Td>
                      {site ? (
                        <Status tone={site.status === 200 ? 'positive' : 'negative'}>
                          {site.status === 200 ? `${site.ms} ms` : (site.status ?? 'down')}
                        </Status>
                      ) : null}
                    </Table.Td>
                    <Table.Td ta="right">
                      <Menu position="bottom-end" withinPortal>
                        <Menu.Target>
                          <Button
                            size="xs"
                            variant="light"
                            rightSection={<IconChevronDown size={14} />}
                          >
                            Do
                          </Button>
                        </Menu.Target>
                        <Menu.Dropdown>
                          {environment === 'dev' ? (
                            <div style={{ padding: 8, width: 240 }}>
                              <Select
                                size="xs"
                                label="Deploy a branch to dev"
                                data={branches}
                                value={branch}
                                onChange={setBranch}
                                searchable
                              />
                              <Button
                                size="xs"
                                mt="xs"
                                fullWidth
                                leftSection={<IconRocket size={14} />}
                                disabled={!branch || running('deploy')}
                                onClick={() => void start('deploy', { environment, ref: branch })}
                              >
                                Deploy {branch}
                              </Button>
                            </div>
                          ) : (
                            <Menu.Item
                              leftSection={<IconRocket size={14} />}
                              disabled={running('deploy')}
                              onClick={() => void start('deploy', { environment })}
                            >
                              {environment === 'staging'
                                ? 'How staging deploys'
                                : `Deploy what staging runs`}
                            </Menu.Item>
                          )}
                          <Menu.Item
                            leftSection={<IconArrowBackUp size={14} />}
                            disabled={!it.previous || running('rollback')}
                            onClick={() => void start('rollback', { environment })}
                          >
                            Roll back to{' '}
                            {it.previous ? short(it.previous.commit) : 'the one before'}
                          </Menu.Item>
                          {environment === 'dev' || environment === 'staging' ? (
                            <Menu.Item
                              leftSection={<IconRefresh size={14} />}
                              color="red"
                              disabled={!it.live || running('fresh')}
                              onClick={() => void start('fresh', { environment })}
                            >
                              Start over, with demo data
                            </Menu.Item>
                          ) : null}
                        </Menu.Dropdown>
                      </Menu>
                    </Table.Td>
                  </Table.Tr>
                );
              })}
            </Table.Tbody>
          </Table>
        </Panel>

        <div className="warden-columns">
          <Panel
            title="Servers"
            icon={IconServer2}
            aside="reached only through Systems Manager: no SSH"
          >
            <Source reading={readings?.cloud}>
              {(servers) => (
                <Stack gap="lg">
                  {servers.length ? null : (
                    <Text size="sm" c="dimmed">
                      No server is made.
                    </Text>
                  )}
                  {servers.map((server) => (
                    <div key={server.id} className="warden-server">
                      <Group justify="space-between" mb="xs">
                        <div>
                          <Group gap="xs">
                            <Text fw={700} tt="capitalize">
                              {server.name}
                            </Text>
                            <Status
                              tone={SERVER_TONE[server.state]}
                              live={server.state === 'pending' || server.state === 'stopping'}
                            >
                              {server.state}
                            </Status>
                          </Group>
                          <Text size="xs" c="dimmed">
                            {server.type} · {server.environments.join(', ')} ·{' '}
                            {server.address ?? 'no address'}
                          </Text>
                        </div>
                        {server.state === 'running' ? (
                          <Confirmed
                            label="Stop it"
                            color="red"
                            question={`Stop the ${server.name} server? ${server.environments.join(' and ')} stop answering until it starts again; its data stays.`}
                            onConfirm={() => void start('stop', { server: server.id })}
                          >
                            <Button
                              size="xs"
                              variant="default"
                              leftSection={<IconPlayerStop size={14} />}
                              disabled={running('stop')}
                            >
                              Stop
                            </Button>
                          </Confirmed>
                        ) : server.state === 'stopped' ? (
                          <Button
                            size="xs"
                            leftSection={<IconPlayerPlay size={14} />}
                            disabled={running('start')}
                            onClick={() => void start('start', { server: server.id })}
                          >
                            Start
                          </Button>
                        ) : null}
                      </Group>
                      {server.error ? (
                        <Text size="sm" c="red">
                          {server.error}
                        </Text>
                      ) : null}
                      {server.state === 'running' && !server.error ? (
                        <Stack gap={8}>
                          <Meter label="Memory" space={server.memory} />
                          <Meter label="Disk" space={server.disk} />
                          <Meter label="Data disk (the environments' data)" space={server.data} />
                          <Group gap="lg">
                            <Text size="xs" c="dimmed">
                              Load {server.load?.[0] ?? '—'} on {server.cores ?? '—'} cores
                            </Text>
                            <Text size="xs" c={server.sessions ? 'yellow' : 'dimmed'}>
                              {server.sessions ?? 0} login sessions
                            </Text>
                            <Text size="xs" c={server.certificate ? 'dimmed' : 'yellow'}>
                              {server.certificate
                                ? `Certificate until ${new Date(server.certificate).toLocaleDateString()}`
                                : 'No certificate yet'}
                            </Text>
                          </Group>
                        </Stack>
                      ) : null}
                    </div>
                  ))}
                  {readings?.server.data?.servers?.map((before) => (
                    <div key={before.host} className="warden-server" data-before>
                      <Group justify="space-between" mb="xs">
                        <div>
                          <Text fw={700}>The server before AWS</Text>
                          <Text size="xs" c="dimmed">
                            {before.host}
                          </Text>
                        </div>
                        <Status tone={before.error ? 'negative' : 'neutral'}>
                          {before.error ? 'does not answer' : 'retiring'}
                        </Status>
                      </Group>
                      <Stack gap={8}>
                        <Meter label="Memory" space={before.memory} />
                        <Meter label="Disk" space={before.disk} />
                      </Stack>
                    </div>
                  ))}
                </Stack>
              )}
            </Source>
          </Panel>

          <Stack gap="lg">
            <Panel title="Infrastructure" icon={IconTopologyStar3} aside="OpenTofu: infra/aws/main">
              <Text size="sm" c="dimmed" mb="sm">
                Check compares what the code declares with what AWS has, and says what would change;
                Apply makes exactly that change, and nothing else.
              </Text>
              {lastCheck ? (
                <Text size="sm" mb="sm">
                  Last check {ago(lastCheck.started)}:{' '}
                  <strong>
                    {lastCheck.state === 'running'
                      ? 'running'
                      : (lastCheck.summary ?? (lastCheck.state === 'failed' ? 'failed' : 'done'))}
                  </strong>
                </Text>
              ) : null}
              <Group>
                <Button
                  variant="default"
                  leftSection={<IconTopologyStar3 size={16} />}
                  disabled={running('check') || running('apply')}
                  onClick={() => void start('check')}
                >
                  Check
                </Button>
                <Confirmed
                  label="Apply the check"
                  color="red"
                  question="Make the changes the last check listed, in AWS, now?"
                  disabled={!checkReadyToApply || running('apply')}
                  onConfirm={() => void start('apply')}
                >
                  <Button disabled={!checkReadyToApply || running('apply')}>Apply the check</Button>
                </Confirmed>
              </Group>
            </Panel>

            <Panel
              title="Backups"
              icon={IconDatabaseExport}
              aside="nightly at 03:00 in Uruguay, kept five weeks"
              flush
            >
              <Source reading={readings?.backups}>
                {(latest) => (
                  <Table verticalSpacing={6} horizontalSpacing="md">
                    <Table.Tbody>
                      {ENVIRONMENTS.filter((env) =>
                        cloud.some((server) => server.environments.includes(env)),
                      ).map((environment) => {
                        const backup = latest[environment];
                        return (
                          <Table.Tr key={environment}>
                            <Table.Td>
                              <Text size="sm" tt="capitalize">
                                {environment}
                              </Text>
                            </Table.Td>
                            <Table.Td>
                              <Text size="sm" c={backup ? undefined : 'dimmed'}>
                                {backup
                                  ? `${backup.day} · database ${bytes(backup.files.database)}, files ${bytes(backup.files.files)}`
                                  : 'none yet'}
                              </Text>
                            </Table.Td>
                            <Table.Td ta="right">
                              <Button
                                size="xs"
                                variant="subtle"
                                disabled={running('backup')}
                                onClick={() => void start('backup', { environment })}
                              >
                                Back up now
                              </Button>
                            </Table.Td>
                          </Table.Tr>
                        );
                      })}
                    </Table.Tbody>
                  </Table>
                )}
              </Source>
            </Panel>
          </Stack>
        </div>

        <Panel
          title="What it is doing"
          icon={IconRocket}
          aside="each action's output, as it comes"
          actions={
            jobs.length ? (
              <Select
                size="xs"
                w={260}
                value={latest}
                onChange={setShown}
                allowDeselect={false}
                data={jobs.map((job) => ({
                  value: job.id,
                  label: `${job.title} · ${ago(job.started)}`,
                }))}
              />
            ) : null
          }
        >
          <JobOutput id={latest} height={360} />
        </Panel>
      </Stack>
    </>
  );
}

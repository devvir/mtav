import { Panel, Status, type Tone } from '@/design';
import { Button, Group, Stack, Text } from '@mantine/core';
import { IconPlayerPlay, IconPlayerStop, IconServer2 } from '@tabler/icons-react';
import type { CloudServer, Readings } from '../../api';
import { Confirmed } from '../../parts/Confirmed';
import { Meter } from '../../parts/Meter';
import { Source } from '../../parts/Source';
import type { Running, Start } from './useActions';

const SERVER_TONE: Record<CloudServer['state'], Tone> = {
  running: 'positive',
  pending: 'info',
  stopping: 'info',
  stopped: 'neutral',
};

/** AWS's servers, each with its state and, while running, how full it is; started and stopped from here */
export function ServersPanel({
  readings,
  start,
  running,
}: {
  readings?: Readings;
  start: Start;
  running: Running;
}) {
  return (
    <Panel title="Servers" icon={IconServer2} aside="reached only through Systems Manager: no SSH">
      <Source reading={readings?.cloud}>
        {(servers) => (
          <Stack gap="lg">
            {servers.length ? null : (
              <Text size="sm" c="dimmed">
                No server is made.
              </Text>
            )}
            {servers.map((server) => (
              <Server key={server.id} server={server} start={start} running={running} />
            ))}
          </Stack>
        )}
      </Source>
    </Panel>
  );
}

/** One server: what and where it is, its state and the button to change it, and how it is doing */
function Server({
  server,
  start,
  running,
}: {
  server: CloudServer;
  start: Start;
  running: Running;
}) {
  return (
    <div className="warden-server">
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
            {server.type} · {server.environments.join(', ')} · {server.address ?? 'no address'}
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
  );
}

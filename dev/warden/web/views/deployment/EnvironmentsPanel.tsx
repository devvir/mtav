import { Panel, Status } from '@/design';
import { Badge, Group, Table, Text, Tooltip } from '@mantine/core';
import { IconStack2 } from '@tabler/icons-react';
import type { Readings } from '../../api';
import { short } from '../../format';
import { EnvironmentActions } from './EnvironmentActions';
import { BRANCH, ENVIRONMENTS, whereIs } from './environments';
import type { Running, Start } from './useActions';

/** Each environment: where it runs, its live release, whether it is behind its branch, its site */
export function EnvironmentsPanel({
  readings,
  start,
  running,
}: {
  readings?: Readings;
  start: Start;
  running: Running;
}) {
  const remote = readings?.repository.data?.remote ?? [];
  const heads = Object.fromEntries(remote.map((one) => [one.name, one.commit]));
  const branches = remote.map((one) => one.name).sort();
  const sites = Object.fromEntries((readings?.sites.data ?? []).map((one) => [one.name, one]));

  return (
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
                </Table.Td>
                <Table.Td>
                  {it.live ? (
                    <Tooltip
                      label={`slot ${it.live.slot}, image ${it.live.digest ? it.live.digest.slice(7, 19) : 'by tag'}${it.previous ? `, before: ${short(it.previous.commit)}` : ''}`}
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
                  <EnvironmentActions
                    environment={environment}
                    live={it.live}
                    previous={it.previous}
                    branches={branches}
                    start={start}
                    running={running}
                  />
                </Table.Td>
              </Table.Tr>
            );
          })}
        </Table.Tbody>
      </Table>
    </Panel>
  );
}

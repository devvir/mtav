import { PageHeader, Panel, Status } from '@/design';
import { Alert, Button, Group, Select, Stack, Text } from '@mantine/core';
import { IconFileText } from '@tabler/icons-react';
import { useState } from 'react';
import { act, type Readings } from '../api';
import { JobOutput } from '../parts/JobOutput';

/** A deployed environment's containers, and the latest lines of the one chosen, read through Systems Manager */
export function Logs({ readings }: { readings?: Readings }) {
  const servers = (readings?.cloud.data ?? []).filter((server) => server.state === 'running');
  const environments = servers.flatMap((server) => server.environments);
  const [environment, setEnvironment] = useState<string | null>(null);
  const [container, setContainer] = useState<string | null>(null);
  const [job, setJob] = useState<string | null>(null);
  const [problem, setProblem] = useState<string | null>(null);

  const chosen = environment ?? environments[0] ?? null;
  const containers = servers
    .flatMap((server) => server.containers ?? [])
    .filter((one) => chosen && one.name.startsWith(`mtav-${chosen}-`));

  const read = async () => {
    setProblem(null);
    try {
      setJob(await act('logs', { environment: chosen, container }));
    } catch (error) {
      setProblem(String((error as Error).message));
    }
  };

  return (
    <>
      <PageHeader
        title="Logs"
        description="The latest lines of any container of a deployed environment."
      />
      <Stack gap="lg">
        <Panel title="Which" icon={IconFileText}>
          {environments.length ? (
            <Group align="flex-end">
              <Select
                label="Environment"
                data={environments}
                value={chosen}
                onChange={(value) => {
                  setEnvironment(value);
                  setContainer(null);
                }}
                allowDeselect={false}
                w={180}
              />
              <Select
                label="Container"
                data={containers.map((one) => ({
                  value: one.name,
                  label: one.name.replace(`mtav-${chosen}-`, ''),
                }))}
                value={container}
                onChange={setContainer}
                w={280}
                renderOption={({ option }) => {
                  const found = containers.find((one) => one.name === option.value);
                  return (
                    <Group justify="space-between" w="100%" wrap="nowrap">
                      <Text size="sm">{option.label}</Text>
                      {found ? (
                        <Status tone={found.state === 'running' ? 'positive' : 'neutral'}>
                          {found.state}
                        </Status>
                      ) : null}
                    </Group>
                  );
                }}
              />
              <Button disabled={!container} onClick={() => void read()}>
                Read its log
              </Button>
            </Group>
          ) : (
            <Text size="sm" c="dimmed">
              No environment runs on a running server.
            </Text>
          )}
          {problem ? (
            <Alert color="red" variant="light" mt="md">
              {problem}
            </Alert>
          ) : null}
        </Panel>
        {job ? (
          <Panel title="Its latest lines" icon={IconFileText}>
            <JobOutput id={job} height={520} />
          </Panel>
        ) : null}
      </Stack>
    </>
  );
}

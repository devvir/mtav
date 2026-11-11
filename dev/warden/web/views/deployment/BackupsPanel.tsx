import { Panel } from '@/design';
import { Button, Table, Text } from '@mantine/core';
import { IconDatabaseExport } from '@tabler/icons-react';
import type { Readings } from '../../api';
import { bytes } from '../../format';
import { Source } from '../../parts/Source';
import { onAws } from './environments';
import type { Running, Start } from './useActions';

/** The latest backup of each environment on AWS, and one taken now */
export function BackupsPanel({
  readings,
  start,
  running,
}: {
  readings?: Readings;
  start: Start;
  running: Running;
}) {
  const cloud = readings?.cloud.data ?? [];

  return (
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
              {onAws(cloud).map((environment) => {
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
  );
}

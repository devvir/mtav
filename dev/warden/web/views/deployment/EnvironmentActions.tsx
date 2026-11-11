import { Button, Menu, Select } from '@mantine/core';
import { IconArrowBackUp, IconChevronDown, IconRefresh, IconRocket } from '@tabler/icons-react';
import { useState } from 'react';
import type { Release } from '../../api';
import { short } from '../../format';
import type { Environment } from './environments';
import type { Running, Start } from './useActions';

/**
 * What can be done with an environment: deploying (any branch, to dev), rolling back to the
 * release before, and, for dev and staging, starting over with demo data
 */
export function EnvironmentActions({
  environment,
  live,
  previous,
  branches,
  start,
  running,
}: {
  environment: Environment;
  live: Release | null;
  previous: Release | null;
  /** The repository's branches, any of which dev deploys */
  branches: string[];
  start: Start;
  running: Running;
}) {
  const [branch, setBranch] = useState<string | null>('dev');

  return (
    <Menu position="bottom-end" withinPortal>
      <Menu.Target>
        <Button size="xs" variant="light" rightSection={<IconChevronDown size={14} />}>
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
            {environment === 'staging' ? 'How staging deploys' : `Deploy what staging runs`}
          </Menu.Item>
        )}
        <Menu.Item
          leftSection={<IconArrowBackUp size={14} />}
          disabled={!previous || running('rollback')}
          onClick={() => void start('rollback', { environment })}
        >
          Roll back to {previous ? short(previous.commit) : 'the one before'}
        </Menu.Item>
        {environment === 'dev' || environment === 'staging' ? (
          <Menu.Item
            leftSection={<IconRefresh size={14} />}
            color="red"
            disabled={!live || running('fresh')}
            onClick={() => void start('fresh', { environment })}
          >
            Start over, with demo data
          </Menu.Item>
        ) : null}
      </Menu.Dropdown>
    </Menu>
  );
}

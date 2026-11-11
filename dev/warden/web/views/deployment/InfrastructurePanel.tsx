import { Panel } from '@/design';
import { Button, Group, Text } from '@mantine/core';
import { IconTopologyStar3 } from '@tabler/icons-react';
import type { Job } from '../../api';
import { ago } from '../../format';
import { Confirmed } from '../../parts/Confirmed';
import type { Running, Start } from './useActions';

/** The infrastructure: OpenTofu's check of what would change, and applying exactly that */
export function InfrastructurePanel({
  jobs,
  start,
  running,
}: {
  jobs: Job[];
  start: Start;
  running: Running;
}) {
  // The latest check, and whether it is still the one to apply
  const lastCheck = jobs.find((job) => job.kind === 'check');
  const lastApply = jobs.find((job) => job.kind === 'apply');
  const checkReadyToApply =
    lastCheck?.state === 'done' && (!lastApply || lastApply.started < lastCheck.started);

  return (
    <Panel title="Infrastructure" icon={IconTopologyStar3} aside="OpenTofu: infra/aws/main">
      <Text size="sm" c="dimmed" mb="sm">
        Check compares what the code declares with what AWS has, and says what would change; Apply
        makes exactly that change, and nothing else.
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
  );
}

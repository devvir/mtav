import { Panel } from '@/design';
import { Select } from '@mantine/core';
import { IconRocket } from '@tabler/icons-react';
import type { Job } from '../../api';
import { ago } from '../../format';
import { JobOutput } from '../../parts/JobOutput';

/** What it is doing: an action's output as it comes, the latest unless another is picked */
export function JobsPanel({
  jobs,
  shown,
  onShow,
}: {
  jobs: Job[];
  shown: string | null;
  onShow: (id: string | null) => void;
}) {
  return (
    <Panel
      title="What it is doing"
      icon={IconRocket}
      aside="each action's output, as it comes"
      actions={
        jobs.length ? (
          <Select
            size="xs"
            w={260}
            value={shown}
            onChange={onShow}
            allowDeselect={false}
            data={jobs.map((job) => ({
              value: job.id,
              label: `${job.title} · ${ago(job.started)}`,
            }))}
          />
        ) : null
      }
    >
      <JobOutput id={shown} height={360} />
    </Panel>
  );
}

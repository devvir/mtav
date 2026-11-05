import { Status } from '@/design';
import { Group, ScrollArea, Text } from '@mantine/core';
import { useEffect, useRef } from 'react';
import { useJob } from '../api';
import { ago } from '../format';

/** A job's output, followed live, kept at its newest line unless someone scrolls up */
export function JobOutput({ id, height = 320 }: { id: string | null; height?: number }) {
  const job = useJob(id);
  const viewport = useRef<HTMLDivElement>(null);
  const following = useRef(true);

  useEffect(() => {
    const element = viewport.current;
    if (element && following.current) element.scrollTop = element.scrollHeight;
  }, [job?.lines.length]);

  if (!job) {
    return (
      <Text size="sm" c="dimmed">
        Nothing asked yet.
      </Text>
    );
  }

  return (
    <div>
      <Group justify="space-between" mb="xs">
        <Text fw={650}>{job.title}</Text>
        <Group gap="sm">
          <Text size="xs" c="dimmed">
            {ago(job.started)}
          </Text>
          <Status
            tone={job.state === 'running' ? 'info' : job.state === 'done' ? 'positive' : 'negative'}
            live={job.state === 'running'}
          >
            {job.state === 'running' ? 'running' : job.state === 'done' ? 'done' : 'failed'}
          </Status>
        </Group>
      </Group>
      <ScrollArea
        h={height}
        viewportRef={viewport}
        className="warden-output"
        onScrollPositionChange={({ y }) => {
          const element = viewport.current;
          if (element) following.current = element.scrollHeight - y - element.clientHeight < 40;
        }}
      >
        <pre>{job.lines.join('\n')}</pre>
      </ScrollArea>
    </div>
  );
}

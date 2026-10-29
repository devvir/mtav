import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { Group, Loader, Progress, Stack, Text } from '@mantine/core';
import { IconCheck } from '@tabler/icons-react';
import { useEffect, useState } from 'react';

/**
 * A lottery under way, step by step, over the time it takes (its end set by the platform):
 * what it reads, weighs, finds and checks. Past its end it waits for the results, which
 * arrive by themselves (and are asked for once, should the live update not come).
 */
export function LotteryProgress({
  startedAt,
  revealAt,
}: {
  startedAt: string;
  revealAt: string | null;
}) {
  const [start, end] = [
    Date.parse(startedAt),
    revealAt ? Date.parse(revealAt) : Date.parse(startedAt),
  ];
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    const tick = window.setInterval(() => setNow(Date.now()), 250);
    const ask = window.setTimeout(
      () => router.reload({ only: ['status', 'pace', 'runs', 'assignment', 'canRank', 'admin'] }),
      Math.max(0, end - Date.now()) + 2500,
    );
    return () => {
      window.clearInterval(tick);
      window.clearTimeout(ask);
    };
  }, [end]);

  const steps = [
    _("Reading every family's preferences"),
    _('Weighing the units of each type'),
    _('Finding the assignment that gives every family the most'),
    _("Checking every family's result"),
  ];
  const share = end > start ? Math.min(1, Math.max(0, (now - start) / (end - start))) : 1;
  const current = Math.min(steps.length - 1, Math.floor(share * steps.length));

  return (
    <Stack gap="md" role="status" aria-live="polite">
      <Group wrap="nowrap">
        <Loader size="md" />
        <Text fw={700} fz="lg">
          {share < 1 ? _('The lottery is running') : _('Writing the results…')}
        </Text>
      </Group>
      <Progress value={share * 100} size="lg" radius="xl" striped animated aria-hidden />
      <Stack gap={6} component="ol" className="lottery-steps">
        {steps.map((step, index) => (
          <Group
            key={step}
            component="li"
            gap="xs"
            wrap="nowrap"
            data-done={index < current || share >= 1 || undefined}
            data-current={(index === current && share < 1) || undefined}
          >
            {index < current || share >= 1 ? (
              <IconCheck size={16} className="text-positive" />
            ) : index === current ? (
              <Loader size={14} />
            ) : (
              <span className="lottery-step-dot" />
            )}
            <Text size="sm">{step}</Text>
          </Group>
        ))}
      </Stack>
    </Stack>
  );
}

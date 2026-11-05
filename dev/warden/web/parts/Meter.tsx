import { Group, Progress, Text } from '@mantine/core';
import type { Space } from '../api';
import { bytes } from '../format';

/** How full something is: a bar that warns as it fills, and the figures */
export function Meter({ label, space }: { label: string; space?: Space }) {
  if (!space?.total) return null;
  const used = space.total - space.free;
  const share = (used / space.total) * 100;
  return (
    <div className="warden-meter">
      <Group justify="space-between" gap="xs" wrap="nowrap">
        <Text size="xs" c="dimmed">
          {label}
        </Text>
        <Text size="xs" className="warden-mono">
          {bytes(used)} of {bytes(space.total)}
        </Text>
      </Group>
      <Progress
        value={share}
        size="sm"
        radius="xl"
        color={share > 90 ? 'red' : share > 75 ? 'yellow' : 'brand'}
      />
    </div>
  );
}

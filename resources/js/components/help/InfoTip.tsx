import { hasTour, startTour } from '@/lib/tour';
import { _ } from '@/lib/translations';
import { ActionIcon, Button, Group, Popover, Text } from '@mantine/core';
import { IconHelpCircle, IconPlayerPlay } from '@tabler/icons-react';
import { useState, type ReactNode } from 'react';

/**
 * A (?) next to something that needs explaining: a short text, and — where a tour exists
 * for it — "Show me how", which walks through it on the page.
 */
export function InfoTip({
  title,
  tour,
  children,
}: {
  title: string;
  tour?: string;
  children: ReactNode;
}) {
  const [opened, setOpened] = useState(false);

  return (
    <Popover opened={opened} onChange={setOpened} position="bottom-start" width={340} withArrow>
      <Popover.Target>
        <ActionIcon
          variant="subtle"
          color="gray"
          radius="xl"
          size="lg"
          aria-label={_('About: {topic}', { topic: title })}
          onClick={() => setOpened((open) => !open)}
        >
          <IconHelpCircle size={21} stroke={1.8} />
        </ActionIcon>
      </Popover.Target>
      <Popover.Dropdown p="md">
        <Text fw={700} mb={4}>
          {title}
        </Text>
        <Text size="sm" c="dimmed" component="div">
          {children}
        </Text>
        <Group justify="space-between" mt="md">
          <Button variant="subtle" size="xs" onClick={() => setOpened(false)}>
            {_('Got it')}
          </Button>
          {tour && hasTour(tour) ? (
            <Button
              size="xs"
              leftSection={<IconPlayerPlay size={14} />}
              onClick={() => {
                setOpened(false);
                setTimeout(() => startTour(tour), 0);
              }}
            >
              {_('Show me how')}
            </Button>
          ) : null}
        </Group>
      </Popover.Dropdown>
    </Popover>
  );
}

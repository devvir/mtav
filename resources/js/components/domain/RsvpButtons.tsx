import { _ } from '@/lib/translations';
import { Button, Group, Stack } from '@mantine/core';
import { IconCheck, IconX } from '@tabler/icons-react';

/**
 * Going or not to an event, the answer given pressed: small and stacked on its card in a
 * list, side by side in its details
 */
export function RsvpButtons({
  attending,
  onAnswer,
  compact = false,
}: {
  attending: boolean | null | undefined;
  onAnswer: (going: boolean) => void;
  compact?: boolean;
}) {
  const icon = compact ? 14 : 18;
  const buttons = (
    <>
      <Button
        size={compact ? 'xs' : undefined}
        variant={attending === true ? 'filled' : 'default'}
        aria-pressed={attending === true}
        leftSection={<IconCheck size={icon} />}
        onClick={() => onAnswer(true)}
      >
        {compact ? _("I'll go") : _('Yes')}
      </Button>
      <Button
        size={compact ? 'xs' : undefined}
        variant={attending === false ? 'light' : 'default'}
        color={attending === false ? 'gray' : undefined}
        aria-pressed={attending === false}
        leftSection={<IconX size={icon} />}
        onClick={() => onAnswer(false)}
      >
        {compact ? _("I can't") : _('No')}
      </Button>
    </>
  );

  return compact ? (
    <Stack gap={6}>{buttons}</Stack>
  ) : (
    <Group grow mt="sm" mb="lg">
      {buttons}
    </Group>
  );
}

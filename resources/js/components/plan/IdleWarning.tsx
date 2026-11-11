import { _, _n } from '@/lib/translations';
import { Button, Group, Modal, Text } from '@mantine/core';

/**
 * The editor left alone: the seconds before it closes (`closesIn`; shown while not null).
 * Any touch keeps it open, so the dialog needs no handler of its own.
 */
export function IdleWarning({ closesIn }: { closesIn: number | null }) {
  return (
    <Modal
      opened={closesIn !== null}
      onClose={() => undefined}
      withCloseButton={false}
      title={_('Still drawing the plan?')}
      centered
    >
      <Text>
        {_n(
          'The plan closes in {count} second and saves what you drew, so others can draw too.',
          'The plan closes in {count} seconds and saves what you drew, so others can draw too.',
          closesIn ?? 0,
        )}
      </Text>
      <Group justify="flex-end" mt="lg">
        {/* Any touch keeps it open: this is the obvious one */}
        <Button data-autofocus>{_('Keep drawing')}</Button>
      </Group>
    </Modal>
  );
}

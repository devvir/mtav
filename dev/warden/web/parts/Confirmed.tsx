import { Button, Group, Modal, Text } from '@mantine/core';
import { useState, type ReactNode } from 'react';

/** A button that asks before doing it */
export function Confirmed({
  label,
  question,
  onConfirm,
  children,
  color,
  disabled,
}: {
  label: string;
  question: ReactNode;
  onConfirm: () => void;
  children: ReactNode;
  color?: string;
  disabled?: boolean;
}) {
  const [open, setOpen] = useState(false);
  return (
    <>
      <span onClick={() => !disabled && setOpen(true)}>{children}</span>
      <Modal opened={open} onClose={() => setOpen(false)} title={label} centered>
        <Text size="sm">{question}</Text>
        <Group justify="flex-end" mt="lg">
          <Button variant="default" onClick={() => setOpen(false)}>
            Cancel
          </Button>
          <Button
            color={color}
            onClick={() => {
              setOpen(false);
              onConfirm();
            }}
          >
            {label}
          </Button>
        </Group>
      </Modal>
    </>
  );
}

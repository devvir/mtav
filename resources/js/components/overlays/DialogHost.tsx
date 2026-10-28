import { dialogQueue, settle, type DialogRequest } from '@/lib/dialogs';
import { useStore } from '@/lib/store';
import { _ } from '@/lib/translations';
import { Button, Group, Modal, Stack, Text, TextInput, Textarea } from '@mantine/core';
import { useState } from 'react';

/**
 * Draws the confirm() and prompt() questions asked from code (lib/dialogs.ts), one at a
 * time. Closing the dialog (Escape, the backdrop, ×) answers no.
 */
export function DialogHost() {
  const request = useStore(dialogQueue)[0];

  return request ? <Question key={request.id} request={request} /> : null;
}

/** One question, with its own answer */
function Question({ request }: { request: DialogRequest }) {
  const [answer, setAnswer] = useState('');

  const { options } = request;
  const canConfirm =
    request.kind === 'confirm'
      ? !request.options.typeToConfirm || answer.trim() === request.options.typeToConfirm
      : answer.trim().length >= (request.options.minLength ?? 1);

  const answerWith = (confirmed: boolean) =>
    settle(request.kind === 'confirm' ? confirmed : confirmed ? answer.trim() : null);

  return (
    <Modal opened onClose={() => answerWith(false)} title={options.title} size="md">
      <form
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          if (canConfirm) answerWith(true);
        }}
      >
        <Stack gap="md">
          {options.message ? <Text c="dimmed">{options.message}</Text> : null}

          {request.kind === 'confirm' && request.options.typeToConfirm ? (
            <TextInput
              label={_('Type {text} to confirm', { text: request.options.typeToConfirm })}
              value={answer}
              onChange={(event) => setAnswer(event.currentTarget.value)}
              autoComplete="off"
              data-autofocus
            />
          ) : null}

          {request.kind === 'prompt' ? (
            request.options.multiline ? (
              <Textarea
                label={request.options.label}
                placeholder={request.options.placeholder}
                value={answer}
                onChange={(event) => setAnswer(event.currentTarget.value)}
                autosize
                minRows={3}
                data-autofocus
              />
            ) : (
              <TextInput
                label={request.options.label}
                placeholder={request.options.placeholder}
                value={answer}
                onChange={(event) => setAnswer(event.currentTarget.value)}
                data-autofocus
              />
            )
          ) : null}

          <Group justify="flex-end" gap="sm" mt="xs">
            <Button variant="default" onClick={() => answerWith(false)}>
              {options.cancelLabel ?? _('Cancel')}
            </Button>
            <Button type="submit" color={options.danger ? 'red' : undefined} disabled={!canConfirm}>
              {options.confirmLabel ?? _('Confirm')}
            </Button>
          </Group>
        </Stack>
      </form>
    </Modal>
  );
}

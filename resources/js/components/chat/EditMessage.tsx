import { useChatRoute } from '@/lib/chat';
import { fieldErrors, sendJson } from '@/lib/json';
import { heldOffline } from '@/lib/offline';
import { _ } from '@/lib/translations';
import { Button, Group, Textarea } from '@mantine/core';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { turnedDown } from './refusals';
import type { ChatMessage } from './types';

/** One's own message, reworded where it is: Enter saves, Escape leaves it as it was */
export function EditMessage({
  message,
  onClose,
  onChange,
}: {
  message: ChatMessage;
  onClose: () => void;
  onChange: () => Promise<void>;
}) {
  const chatRoute = useChatRoute();
  const [body, setBody] = useState(message.body);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string>();
  const field = useRef<HTMLTextAreaElement>(null);

  // Into the words at once, at their end
  useEffect(() => {
    const element = field.current;
    element?.focus();
    element?.setSelectionRange(element.value.length, element.value.length);
  }, []);

  const save = async (event?: FormEvent) => {
    event?.preventDefault();
    if (!body.trim() || saving) return;
    if (body.trim() === message.body) return onClose();
    if (heldOffline()) return;
    setSaving(true);
    setError(undefined);
    const response = await sendJson('PUT', chatRoute('messages.update', { message: message.id }), {
      body,
    });
    setSaving(false);
    if (response.status === 422) {
      setError((await fieldErrors(response)).body);
      return;
    }
    if (!response.ok) {
      await turnedDown(response);
      // Past its hour it stays as it was: nothing left to edit
      if (response.status !== 409) return;
    }
    await onChange();
    onClose();
  };

  return (
    <form className="chat-edit" onSubmit={(event) => void save(event)}>
      <Textarea
        ref={field}
        aria-label={_('Edit your message')}
        autosize
        minRows={1}
        maxRows={6}
        value={body}
        error={error}
        onChange={(event) => setBody(event.currentTarget.value)}
        onKeyDown={(event) => {
          if (event.key === 'Escape') {
            // The edit, not the window around it
            event.preventDefault();
            event.stopPropagation();
            onClose();
          } else if (event.key === 'Enter' && !event.shiftKey && !event.nativeEvent.isComposing) {
            event.preventDefault();
            void save();
          }
        }}
      />
      <Group gap="xs" justify="flex-end" mt={6}>
        <Button size="xs" variant="default" onClick={onClose}>
          {_('Cancel')}
        </Button>
        <Button size="xs" type="submit" loading={saving} disabled={!body.trim()}>
          {_('Save')}
        </Button>
      </Group>
    </form>
  );
}

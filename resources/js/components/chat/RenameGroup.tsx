import { useChatProject } from '@/lib/chat';
import { sendJson } from '@/lib/json';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Button, Group, Modal, TextInput } from '@mantine/core';
import { useState, type FormEvent } from 'react';
import { refused } from './refusals';
import type { OpenConversation } from './types';

/** A group's new name, asked in a dialog */
export function RenameGroup({
  opened,
  onClose,
  conversation,
  onChange,
}: {
  opened: boolean;
  onClose: () => void;
  conversation: OpenConversation;
  onChange: () => Promise<void>;
}) {
  const { project } = useChatProject();
  const [title, setTitle] = useState(conversation.name ?? '');
  const [saving, setSaving] = useState(false);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    const url = route('chat.update', { project: project!.id, conversation: conversation.id });
    if ((await sendJson('PUT', url, { title })).ok) {
      await onChange();
      onClose();
    } else refused();
    setSaving(false);
  };

  return (
    <Modal opened={opened} onClose={onClose} title={_('Rename the group')}>
      <form onSubmit={(event) => void save(event)}>
        <TextInput
          label={_('Group name')}
          data-autofocus
          maxLength={80}
          value={title}
          onChange={(event) => setTitle(event.currentTarget.value)}
        />
        <Group justify="flex-end" mt="lg">
          <Button type="submit" loading={saving}>
            {_('Save')}
          </Button>
        </Group>
      </form>
    </Modal>
  );
}

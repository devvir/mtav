import { showConversation, useChatProject } from '@/lib/chat';
import { fieldErrors, sendJson } from '@/lib/json';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Button, Group, Modal, MultiSelect, Stack, TextInput } from '@mantine/core';
import { useRef, useState, type FormEvent } from 'react';
import { refused } from './refusals';
import type { ChatPerson } from './types';

/** Starting a conversation: one person (theirs, if there is one already) or a group */
export function NewConversation({
  opened,
  onClose,
  people,
  onChange,
}: {
  opened: boolean;
  onClose: () => void;
  people: ChatPerson[];
  onChange: () => Promise<void>;
}) {
  const { project } = useChatProject();
  const [chosen, setChosen] = useState<string[]>([]);
  const [title, setTitle] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string>();
  const group = chosen.length > 1;
  const picker = useRef<HTMLInputElement>(null);

  const start = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setError(undefined);
    const response = await sendJson('POST', route('chat.store', { project: project!.id }), {
      people: chosen.map(Number),
      title: group ? title : null,
    });
    if (response.ok) {
      const { conversation } = (await response.json()) as { conversation: number };
      setChosen([]);
      setTitle('');
      showConversation(conversation);
      await onChange();
      onClose();
    } else if (response.status === 422) {
      setError((await fieldErrors(response)).people);
    } else refused();
    setSaving(false);
  };

  return (
    <Modal opened={opened} onClose={onClose} title={_('New conversation')}>
      <form onSubmit={(event) => void start(event)}>
        <Stack>
          <MultiSelect
            label={_('With')}
            description={_('One person, or several for a group.')}
            data-autofocus
            searchable
            data={people.map((person) => ({ value: String(person.id), label: person.name }))}
            value={chosen}
            error={error}
            onChange={setChosen}
            // Picked: the list out of the way of what comes next (one more, a click away)
            ref={picker}
            onOptionSubmit={() => picker.current?.blur()}
            nothingFoundMessage={_('Nobody else in the project goes by that name')}
          />
          {group ? (
            <TextInput
              label={_('Group name')}
              placeholder={_('Building committee, Block A…')}
              maxLength={80}
              value={title}
              onChange={(event) => setTitle(event.currentTarget.value)}
            />
          ) : null}
        </Stack>
        <Group justify="flex-end" mt="lg">
          <Button type="submit" loading={saving} disabled={!chosen.length}>
            {group ? _('Start the group') : _('Start the conversation')}
          </Button>
        </Group>
      </form>
    </Modal>
  );
}

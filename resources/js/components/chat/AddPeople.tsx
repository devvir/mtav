import { useChatProject } from '@/lib/chat';
import { fieldErrors, sendJson } from '@/lib/json';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Button, Group, Modal, MultiSelect } from '@mantine/core';
import { useRef, useState, type FormEvent } from 'react';
import { refused } from './refusals';
import type { ChatPerson, OpenConversation } from './types';

/** More people into a group, picked in a dialog among those of the project not in it yet */
export function AddPeople({
  opened,
  onClose,
  conversation,
  people,
  onChange,
}: {
  opened: boolean;
  onClose: () => void;
  conversation: OpenConversation;
  people: ChatPerson[];
  onChange: () => Promise<void>;
}) {
  const { project } = useChatProject();
  const [chosen, setChosen] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string>();
  const picker = useRef<HTMLInputElement>(null);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    const url = route('chat.update', { project: project!.id, conversation: conversation.id });
    const response = await sendJson('PUT', url, { people: chosen.map(Number) });
    if (response.ok) {
      setChosen([]);
      await onChange();
      onClose();
    } else if (response.status === 422) {
      setError((await fieldErrors(response)).people);
    } else refused();
    setSaving(false);
  };

  return (
    <Modal opened={opened} onClose={onClose} title={_('Add people')}>
      <form onSubmit={(event) => void save(event)}>
        <MultiSelect
          label={_('People')}
          data-autofocus
          searchable
          data={people.map((person) => ({ value: String(person.id), label: person.name }))}
          value={chosen}
          error={error}
          onChange={setChosen}
          ref={picker}
          onOptionSubmit={() => picker.current?.blur()}
          nothingFoundMessage={_('Nobody else in the project goes by that name')}
        />
        <Group justify="flex-end" mt="lg">
          <Button type="submit" loading={saving} disabled={!chosen.length}>
            {_('Add')}
          </Button>
        </Group>
      </form>
    </Modal>
  );
}

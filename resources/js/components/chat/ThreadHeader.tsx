import { showConversation, useChatRoute } from '@/lib/chat';
import { confirm } from '@/lib/dialogs';
import { sendJson } from '@/lib/json';
import { _ } from '@/lib/translations';
import { ActionIcon, Group, Menu, Text } from '@mantine/core';
import {
  IconArrowLeft,
  IconDoorExit,
  IconDots,
  IconPencil,
  IconUserPlus,
} from '@tabler/icons-react';
import { useState } from 'react';
import { AddPeople } from './AddPeople';
import { Faces } from './Faces';
import { refused } from './refusals';
import { RenameGroup } from './RenameGroup';
import type { ChatPerson, OpenConversation } from './types';
import { WindowButtons } from './WindowButtons';

/**
 * The open conversation's heading: back to the list, who is in it, and what can be done with
 * a group (renaming it, adding people, leaving it)
 */
export function ThreadHeader({
  conversation,
  people,
  alone,
  popup,
  onChange,
}: {
  conversation: OpenConversation;
  /** Whom one may talk with: those not in it yet can be added */
  people: ChatPerson[];
  /** The only conversation there is: no way back to a list */
  alone: boolean;
  popup: boolean;
  onChange: () => Promise<void>;
}) {
  const chatRoute = useChatRoute();
  const [renaming, setRenaming] = useState(false);
  const [adding, setAdding] = useState(false);
  const names = conversation.people.map((person) => person.name).join(', ');

  const leave = async () => {
    if (
      await confirm({
        title: _('Leave {name}?', { name: conversation.title }),
        message: _('You will no longer see its messages.'),
        confirmLabel: _('Leave'),
        danger: true,
      })
    ) {
      if (
        (await sendJson('DELETE', chatRoute('leave', { conversation: conversation.id }), {})).ok
      ) {
        showConversation(null);
        await onChange();
      } else refused();
    }
  };

  return (
    <>
      <header className="chat-thread-header">
        <Group gap="sm" wrap="nowrap" style={{ minWidth: 0 }}>
          {alone ? null : (
            <ActionIcon
              onClick={() => showConversation(null)}
              variant="subtle"
              className="chat-back"
              aria-label={_('All conversations')}
            >
              <IconArrowLeft size={20} />
            </ActionIcon>
          )}
          <Faces conversation={conversation} />
          <div style={{ minWidth: 0 }}>
            <Text fw={700} truncate title={conversation.title}>
              {conversation.title}
            </Text>
            {conversation.is_group ? (
              <Text className="ds-caption" truncate title={names}>
                {names}
              </Text>
            ) : null}
          </div>
        </Group>
        <Group gap={4} wrap="nowrap">
          {conversation.can.update || conversation.can.leave ? (
            <Menu position="bottom-end" withinPortal>
              <Menu.Target>
                <ActionIcon variant="subtle" aria-label={_('Conversation options')}>
                  <IconDots size={20} />
                </ActionIcon>
              </Menu.Target>
              <Menu.Dropdown>
                {conversation.can.update ? (
                  <>
                    <Menu.Item
                      leftSection={<IconPencil size={16} />}
                      onClick={() => setRenaming(true)}
                    >
                      {_('Rename the group')}
                    </Menu.Item>
                    <Menu.Item
                      leftSection={<IconUserPlus size={16} />}
                      onClick={() => setAdding(true)}
                    >
                      {_('Add people')}
                    </Menu.Item>
                  </>
                ) : null}
                {conversation.can.leave ? (
                  <Menu.Item color="red" leftSection={<IconDoorExit size={16} />} onClick={leave}>
                    {_('Leave the group')}
                  </Menu.Item>
                ) : null}
              </Menu.Dropdown>
            </Menu>
          ) : null}
          {popup ? <WindowButtons /> : null}
        </Group>
      </header>

      <RenameGroup
        // Its name as it is now, each time it opens
        key={String(renaming)}
        opened={renaming}
        onClose={() => setRenaming(false)}
        conversation={conversation}
        onChange={onChange}
      />
      <AddPeople
        opened={adding}
        onClose={() => setAdding(false)}
        conversation={conversation}
        people={people.filter((person) => !conversation.people.some((one) => one.id === person.id))}
        onChange={onChange}
      />
    </>
  );
}

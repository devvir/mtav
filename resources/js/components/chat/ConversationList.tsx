import { DateTime } from '@/components/feedback/DateTime';
import { showConversation, useChatRoute } from '@/lib/chat';
import { sendJson } from '@/lib/json';
import { _ } from '@/lib/translations';
import {
  ActionIcon,
  Button,
  Group,
  SegmentedControl,
  Text,
  TextInput,
  VisuallyHidden,
} from '@mantine/core';
import { IconPin, IconPinFilled, IconPlus } from '@tabler/icons-react';
import { useState } from 'react';
import { assistantGreeting } from './assistantGreeting';
import { Faces } from './Faces';
import { refused } from './refusals';
import type { ConversationSummary } from './types';
import { WindowButtons } from './WindowButtons';

/** A list this long gets a search */
const SEARCH_FROM = 12;

/** What a conversation's row says under its title: its latest message, and who wrote it */
const lastLine = (conversation: ConversationSummary) => {
  const { last } = conversation;
  if (!last) {
    // The Assistant's greeting is there before anything is written
    return conversation.is_assistant ? assistantGreeting() : _('No messages yet');
  }
  const by = last.mine
    ? _('You') + ': '
    : conversation.is_group && last.author
      ? last.author + ': '
      : '';
  return `${by}${last.body}`;
};

/** Someone's conversations, the latest first, chats and groups apart; the unread marked */
export function ConversationList({
  conversations,
  open,
  onNew,
  popup,
  onChange,
}: {
  conversations: ConversationSummary[];
  open: number | null;
  onNew: () => void;
  popup: boolean;
  onChange: () => Promise<void>;
}) {
  const chatRoute = useChatRoute();
  const [query, setQuery] = useState('');
  const groups = conversations.filter((conversation) => conversation.is_group);
  const chats = conversations.filter((conversation) => !conversation.is_group);
  const [kind, setKind] = useState<'chats' | 'groups'>(
    conversations.find((conversation) => conversation.id === open)?.is_group || !chats.length
      ? 'groups'
      : 'chats',
  );
  // The pinned first; within each, as they come, the latest first
  const words = query.trim().toLowerCase();
  const listed = (kind === 'chats' ? chats : groups)
    .filter((conversation) => conversation.title.toLowerCase().includes(words))
    .sort((one, other) => Number(other.pinned ?? false) - Number(one.pinned ?? false));
  const pin = async (conversation: ConversationSummary) => {
    const url = chatRoute('pin', { conversation: conversation.id });
    if ((await sendJson('PUT', url, { pinned: !conversation.pinned })).ok) await onChange();
    else refused();
  };
  const marked = (name: string, list: ConversationSummary[]) =>
    list.some((conversation) => conversation.unread) ? `${name} •` : name;

  return (
    <nav className="chat-list ds-surface" aria-label={_('Conversations')} data-tour="chat.list">
      <div className="chat-kinds">
        <Group justify="space-between" wrap="nowrap" mb="xs">
          <Text fw={700}>{_('Messages')}</Text>
          <Group gap={4} wrap="nowrap">
            <Button
              size="xs"
              variant="light"
              leftSection={<IconPlus size={16} />}
              data-tour="chat.new"
              onClick={onNew}
            >
              {_('New conversation')}
            </Button>
            {popup ? <WindowButtons /> : null}
          </Group>
        </Group>
        <SegmentedControl
          fullWidth
          size="xs"
          value={kind}
          onChange={(value) => setKind(value as 'chats' | 'groups')}
          data={[
            { value: 'chats', label: marked(_('Chats'), chats) },
            { value: 'groups', label: marked(_('Groups'), groups) },
          ]}
        />
      </div>
      {conversations.length > SEARCH_FROM ? (
        <div className="chat-search">
          <TextInput
            size="xs"
            aria-label={_('Search conversations')}
            placeholder={_('Search conversations')}
            value={query}
            onChange={(event) => setQuery(event.currentTarget.value)}
          />
        </div>
      ) : null}
      {!listed.length ? (
        <Text c="dimmed" ta="center" p="lg" size="sm">
          {words
            ? _('Nothing matches')
            : kind === 'chats'
              ? _('No one-on-one chats yet.')
              : _('No groups yet.')}
        </Text>
      ) : null}
      {listed.map((conversation) => (
        <div key={conversation.id} className="chat-list-row">
          <button
            type="button"
            onClick={() => showConversation(conversation.id)}
            className="chat-list-item"
            aria-current={conversation.id === open ? 'page' : undefined}
            data-unread={conversation.unread || undefined}
          >
            <Faces conversation={conversation} />
            <div style={{ flex: 1, minWidth: 0 }}>
              <Group justify="space-between" gap="xs" wrap="nowrap">
                <Text fw={conversation.unread ? 750 : 650} truncate title={conversation.title}>
                  {conversation.title}
                </Text>
                {conversation.last ? (
                  <Text className="ds-caption" style={{ flex: 'none' }}>
                    <DateTime value={conversation.last.at} relative />
                  </Text>
                ) : null}
              </Group>
              <Text
                size="sm"
                c="dimmed"
                truncate
                title={lastLine(conversation)}
                className={conversation.last || conversation.is_assistant ? undefined : 'chat-note'}
              >
                {lastLine(conversation)}
              </Text>
            </div>
            {conversation.unread ? (
              <span className="chat-unread">
                <VisuallyHidden component="span">{_('Unread')}</VisuallyHidden>
              </span>
            ) : null}
          </button>
          <ActionIcon
            variant="subtle"
            color="gray"
            size="sm"
            className="chat-pin"
            data-pinned={conversation.pinned || undefined}
            aria-pressed={Boolean(conversation.pinned)}
            aria-label={conversation.pinned ? _('Unpin') : _('Pin')}
            onClick={() => void pin(conversation)}
          >
            {conversation.pinned ? <IconPinFilled size={14} /> : <IconPin size={14} />}
          </ActionIcon>
        </div>
      ))}
    </nav>
  );
}

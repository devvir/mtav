import { chatState, showConversation, useChatProject } from '@/lib/chat';
import { _ } from '@/lib/translations';
import { Skeleton, Text } from '@mantine/core';
import { useEffect, useState } from 'react';
import { ConversationList } from './ConversationList';
import { NewConversation } from './NewConversation';
import { Thread } from './Thread';
import type { ChatData } from './types';
import { useChatPanel } from './useChatPanel';

/**
 * The project's chat, the same in the window that opens from its bubble and on its page (where
 * phones have it): someone's conversations and the one open, one at a time where there is no
 * room for both. It never leaves the page it is on: it asks the server on its own.
 */
export function Chat({ initial, popup = false }: { initial?: ChatData; popup?: boolean }) {
  const { project } = useChatProject();
  const { data, open, load, earlier } = useChatPanel(initial);
  const [starting, setStarting] = useState(false);

  // Outside any project the chat is the Assistant alone: its conversation is the one open
  const alone = !project;
  const assistant = alone ? data?.conversation?.id : undefined;
  useEffect(() => {
    if (assistant && chatState().conversation !== assistant) showConversation(assistant);
  }, [assistant]);

  if (!data) {
    return (
      <div className="chat">
        <Skeleton h="100%" radius="lg" />
      </div>
    );
  }

  if (alone) {
    return (
      <div className="chat" data-open data-alone>
        {data.conversation && data.messages ? (
          <Thread
            key={data.conversation.id}
            conversation={data.conversation}
            messages={data.messages}
            people={[]}
            alone
            popup={popup}
            onChange={() => load()}
            onEarlier={earlier}
          />
        ) : (
          <Skeleton h="100%" radius="lg" />
        )}
      </div>
    );
  }

  const shown = data.conversation?.id === open ? data.conversation : null;

  return (
    <div className="chat" data-open={open ? true : undefined}>
      <ConversationList
        conversations={data.conversations}
        open={open}
        popup={popup && !open}
        onNew={() => setStarting(true)}
        onChange={() => load()}
      />
      {shown && data.messages ? (
        <Thread
          key={shown.id}
          conversation={shown}
          messages={data.messages}
          people={data.people}
          popup={popup}
          onChange={() => load()}
          onEarlier={earlier}
        />
      ) : (
        <div className="chat-thread chat-none ds-surface">
          {open ? (
            <Skeleton h="100%" w="100%" radius="md" />
          ) : (
            <Text c="dimmed">{_('Pick a conversation, or start a new one.')}</Text>
          )}
        </div>
      )}
      <NewConversation
        opened={starting}
        onClose={() => setStarting(false)}
        people={data.people}
        onChange={() => load()}
      />
    </div>
  );
}

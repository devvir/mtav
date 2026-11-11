import { useChatRoute } from '@/lib/chat';
import { changeableAt, useChangeClock, useShownMessages } from '@/lib/chatMessages';
import { confirm } from '@/lib/dialogs';
import { fieldErrors, sendJson } from '@/lib/json';
import { heldOffline } from '@/lib/offline';
import { _, languageTag } from '@/lib/translations';
import { Button, Group, Text } from '@mantine/core';
import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { AssistantWelcome } from './AssistantWelcome';
import { Composer } from './Composer';
import { MessageEntry } from './MessageEntry';
import { refused, turnedDown } from './refusals';
import { ThreadHeader } from './ThreadHeader';
import type { ChatData, ChatMessage, ChatPerson, OpenConversation } from './types';

/** Its edit and delete buttons, to give the focus back to once editing ends */
const changesOf = (message: number) =>
  document.querySelector<HTMLElement>(`[data-message="${message}"] .chat-change`);

/** The open conversation: its messages, the latest at the bottom, and writing a new one */
export function Thread({
  conversation,
  messages,
  people,
  alone = false,
  popup,
  onChange,
  onEarlier,
}: {
  conversation: OpenConversation;
  messages: NonNullable<ChatData['messages']>;
  people: ChatPerson[];
  /** The only conversation there is (the Assistant, outside any project): no way back to a list */
  alone?: boolean;
  popup: boolean;
  onChange: () => Promise<void>;
  /** More of the earlier messages, asked for */
  onEarlier: () => Promise<void>;
}) {
  const chatRoute = useChatRoute();
  const scroller = useRef<HTMLDivElement>(null);
  const atBottom = useRef(true);
  const [body, setBody] = useState('');
  const [sending, setSending] = useState(false);
  const [bodyError, setBodyError] = useState<string>();
  // The message being edited, and the one whose editing just ended (its words come back)
  const [editing, setEditing] = useState<number | null>(null);
  const [edited, setEdited] = useState<number | null>(null);
  const { shown, reworded } = useShownMessages(messages.data);
  const now = useChangeClock(messages.data);
  const last = messages.data.at(-1)?.id;
  const clock = useMemo(() => new Intl.DateTimeFormat(languageTag(), { timeStyle: 'short' }), []);

  // The latest in view as the conversation opens and as messages arrive (unless reading
  // further up)
  useLayoutEffect(() => {
    const element = scroller.current;
    if (element && atBottom.current) element.scrollTop = element.scrollHeight;
  }, [last, conversation.id]);

  // Editing ended: the focus back on the message's buttons, where it was
  useEffect(() => {
    if (edited !== null) changesOf(edited)?.focus();
  }, [edited]);

  /** What is written, or a reply tapped under the Assistant's words */
  const send = async (words = body) => {
    if (!words.trim() || sending || heldOffline()) return;
    atBottom.current = true;
    setSending(true);
    setBodyError(undefined);
    const response = await sendJson('POST', chatRoute('send', { conversation: conversation.id }), {
      body: words,
    });
    if (response.ok) {
      setBody('');
      await onChange();
    } else if (response.status === 422) {
      setBodyError((await fieldErrors(response)).body);
    } else {
      refused();
    }
    setSending(false);
  };

  const earlier = async () => {
    const element = scroller.current;
    const from = element ? element.scrollHeight - element.scrollTop : 0;
    atBottom.current = false;
    await onEarlier();
    requestAnimationFrame(() => {
      // Where one was reading stays in place, the earlier ones above it
      if (element) element.scrollTop = element.scrollHeight - from;
    });
  };

  const remove = async (message: ChatMessage) => {
    if (heldOffline()) return;
    const sure = await confirm({
      title: _('Delete this message?'),
      message: conversation.is_assistant
        ? _("It will disappear, along with the Assistant's answer to it.")
        : _('It will disappear for everyone in the conversation.'),
      confirmLabel: _('Delete'),
      danger: true,
    });
    if (!sure || heldOffline()) return;
    const response = await sendJson(
      'DELETE',
      chatRoute('messages.destroy', { message: message.id }),
      {},
    );
    if (!response.ok) await turnedDown(response);
    await onChange();
  };

  return (
    <section className="chat-thread ds-surface" aria-label={conversation.title}>
      <ThreadHeader
        conversation={conversation}
        people={people}
        alone={alone}
        popup={popup}
        onChange={onChange}
      />

      <div
        ref={scroller}
        className="chat-messages"
        role="log"
        aria-live="polite"
        aria-relevant="additions"
        aria-label={conversation.title}
        onScroll={(event) => {
          const element = event.currentTarget;
          atBottom.current = element.scrollHeight - element.scrollTop - element.clientHeight < 80;
        }}
      >
        {messages.earlier ? (
          <Group justify="center" mb="sm">
            <Button variant="subtle" size="xs" onClick={() => void earlier()}>
              {_('Earlier messages')}
            </Button>
          </Group>
        ) : null}
        {messages.data.length || conversation.is_assistant ? null : (
          <Text c="dimmed" ta="center" py="xl" className="chat-note">
            {_('Say hello: the first message starts the conversation.')}
          </Text>
        )}
        {conversation.is_assistant && !messages.earlier ? (
          <AssistantWelcome asking={!messages.data.length} onAsk={(words) => void send(words)} />
        ) : null}
        {shown.map(({ message, leaving }, index) => (
          <MessageEntry
            key={message.id}
            message={message}
            before={shown[index - 1]?.message}
            conversation={conversation}
            leaving={leaving}
            editing={editing === message.id}
            back={edited === message.id}
            reworded={reworded.has(message.id)}
            changeable={message.mine && !leaving && changeableAt(message, now)}
            clock={clock}
            onEdit={() => {
              setEdited(null);
              setEditing(message.id);
            }}
            onEditEnd={() => {
              setEditing(null);
              setEdited(message.id);
            }}
            onDelete={() => void remove(message)}
            onReply={(words) => void send(words)}
            onChange={onChange}
          />
        ))}
      </div>

      {conversation.answering ? (
        <div className="chat-answering" role="status" aria-label={_('The Assistant is writing')}>
          <span />
          <span />
          <span />
        </div>
      ) : null}
      <Composer
        body={body}
        error={bodyError}
        sending={sending}
        onType={setBody}
        onSend={() => void send()}
      />
    </section>
  );
}

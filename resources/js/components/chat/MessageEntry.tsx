import { _ } from '@/lib/translations';
import { Text, VisuallyHidden } from '@mantine/core';
import { AssistantActions } from './AssistantActions';
import { AssistantFace } from './AssistantFace';
import { dayLabel, dayOf } from './days';
import { EditMessage } from './EditMessage';
import { MessageChanges } from './MessageChanges';
import type { ChatMessage, OpenConversation } from './types';

/**
 * One message among the others: under its day's heading where a new day starts, its writer's
 * name in a group where the writer changes, its words (or the edit of them) and, one's own
 * within its hour, the buttons to change it. A deleted one stays a moment as it leaves.
 */
export function MessageEntry({
  message,
  before,
  conversation,
  leaving,
  editing,
  back,
  reworded,
  changeable,
  clock,
  onEdit,
  onEditEnd,
  onDelete,
  onReply,
  onChange,
}: {
  message: ChatMessage;
  /** The one shown above it, if any */
  before?: ChatMessage;
  conversation: OpenConversation;
  /** Deleted: it fades, its room closing */
  leaving: boolean;
  /** Being edited: the edit shows in place of its words */
  editing: boolean;
  /** Its editing just ended: its words come back */
  back: boolean;
  /** Its words just changed: the new ones come in gently */
  reworded: boolean;
  /** Its writer may still edit or delete it */
  changeable: boolean;
  clock: Intl.DateTimeFormat;
  onEdit: () => void;
  onEditEnd: () => void;
  onDelete: () => void;
  onReply: (words: string) => void;
  onChange: () => Promise<void>;
}) {
  const newDay = !before || dayOf(before.created_at) !== dayOf(message.created_at);
  const sameAuthor = !newDay && before?.author?.id === message.author?.id;
  const writer = message.author?.name ?? _('Someone who left');

  return (
    <div
      className="chat-entry"
      data-leaving={leaving || undefined}
      aria-hidden={leaving || undefined}
    >
      <div className="chat-entry-inner">
        {newDay ? <div className="chat-day">{dayLabel(message.created_at)}</div> : null}
        <div
          className="chat-message"
          data-message={message.id}
          data-mine={message.mine || undefined}
          data-follows={sameAuthor || undefined}
          data-editing={editing || undefined}
        >
          {message.from_assistant && !sameAuthor ? <AssistantFace size={28} /> : null}
          {!message.mine && conversation.is_group && !sameAuthor ? (
            <Text className="chat-author" aria-hidden>
              {writer}
            </Text>
          ) : null}
          {editing ? (
            <EditMessage message={message} onClose={onEditEnd} onChange={onChange} />
          ) : (
            <div className="chat-bubble" data-back={back || undefined}>
              <VisuallyHidden>
                {message.mine ? _('You') : message.from_assistant ? _('Assistant') : writer}:
              </VisuallyHidden>
              <Text
                // New words come in gently, wherever they were changed
                key={message.body}
                component="div"
                className="chat-body"
                data-reworded={reworded || undefined}
              >
                {message.body}
              </Text>
              <time className="chat-time" dateTime={message.created_at}>
                {clock.format(new Date(message.created_at))}
              </time>
            </div>
          )}
          {message.actions?.length ? (
            <AssistantActions
              message={message.id}
              actions={message.actions}
              onReply={onReply}
              onChange={onChange}
            />
          ) : null}
          <MessageChanges
            shown={changeable}
            editable={!conversation.is_assistant}
            onEdit={onEdit}
            onDelete={onDelete}
          />
        </div>
      </div>
    </div>
  );
}

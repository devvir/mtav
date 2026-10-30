import { DateTime, formatDate } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { heldOffline } from '@/lib/offline';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
  ActionIcon,
  Avatar,
  Button,
  Group,
  Menu,
  Modal,
  MultiSelect,
  Stack,
  Text,
  Textarea,
  TextInput,
} from '@mantine/core';
import {
  IconArrowLeft,
  IconDoorExit,
  IconDots,
  IconMessageCircle,
  IconPencil,
  IconPlus,
  IconSend2,
  IconTrash,
  IconUserPlus,
} from '@tabler/icons-react';
import { useEffect, useLayoutEffect, useRef, useState, type FormEvent } from 'react';

export interface ChatPerson {
  id: number;
  name: string;
  avatar: string | null;
}

interface ConversationSummary {
  id: number;
  title: string;
  is_group: boolean;
  faces: ChatPerson[];
  last: { body: string; author: string | null; mine: boolean; at: string } | null;
  unread?: boolean;
}

interface ChatMessage {
  id: number;
  body: string;
  author: ChatPerson | null;
  mine: boolean;
  created_at: string;
}

interface Props {
  conversations: ConversationSummary[];
  conversation:
    | (ConversationSummary & { people: ChatPerson[]; can: { update: boolean; leave: boolean } })
    | null;
  messages: { data: ChatMessage[]; earlier: boolean } | null;
  people: ChatPerson[];
}

/** Earlier messages come this many at a time */
const MORE = 50;

/** A conversation's faces: the other person, or a group's first few, overlapping */
function Faces({ conversation }: { conversation: ConversationSummary }) {
  if (!conversation.faces.length) return <Avatar radius="xl" size={40} />;
  if (!conversation.is_group) {
    const [person] = conversation.faces;
    return <PersonAvatar name={person.name} src={person.avatar} size={40} />;
  }
  return (
    <Avatar.Group spacing="md">
      {conversation.faces.slice(0, 2).map((person) => (
        <PersonAvatar key={person.id} name={person.name} src={person.avatar} size={32} />
      ))}
    </Avatar.Group>
  );
}

/** Someone's conversations, the latest first; the open one lit, the unread marked */
function ConversationList({
  conversations,
  open,
}: {
  conversations: ConversationSummary[];
  open: number | null;
}) {
  const { project } = usePage().props;

  return (
    <nav className="chat-list ds-surface" aria-label={_('Conversations')} data-tour="chat.list">
      {conversations.map((conversation) => (
        <Link
          key={conversation.id}
          href={route('chat.show', { project: project!.id, conversation: conversation.id })}
          className="chat-list-item"
          aria-current={conversation.id === open ? 'page' : undefined}
          data-unread={conversation.unread || undefined}
          preserveScroll
        >
          <Faces conversation={conversation} />
          <div style={{ flex: 1, minWidth: 0 }}>
            <Group justify="space-between" gap="xs" wrap="nowrap">
              <Text fw={conversation.unread ? 750 : 650} truncate>
                {conversation.title}
              </Text>
              {conversation.last ? (
                <Text className="ds-caption" style={{ flex: 'none' }}>
                  <DateTime value={conversation.last.at} relative />
                </Text>
              ) : null}
            </Group>
            <Text size="sm" c="dimmed" truncate>
              {conversation.last
                ? `${conversation.last.mine ? _('You') + ': ' : conversation.is_group && conversation.last.author ? conversation.last.author + ': ' : ''}${conversation.last.body}`
                : _('No messages yet')}
            </Text>
          </div>
          {conversation.unread ? <span className="chat-unread" aria-label={_('Unread')} /> : null}
        </Link>
      ))}
    </nav>
  );
}

/** A day's heading among the messages: today, yesterday, or its date */
const dayOf = (value: string) => new Date(value).toDateString();
function dayLabel(value: string) {
  const day = new Date(value);
  const today = new Date();
  const yesterday = new Date(Date.now() - 86_400_000);
  if (day.toDateString() === today.toDateString()) return _('Today');
  if (day.toDateString() === yesterday.toDateString()) return _('Yesterday');
  return formatDate(value, false);
}

/** The open conversation: its messages, the latest at the bottom, and writing a new one */
function Thread({
  conversation,
  messages,
  people,
}: {
  conversation: NonNullable<Props['conversation']>;
  messages: NonNullable<Props['messages']>;
  people: ChatPerson[];
}) {
  const { project } = usePage().props;
  const at = { project: project!.id, conversation: conversation.id };
  const scroller = useRef<HTMLDivElement>(null);
  const atBottom = useRef(true);
  const form = useForm({ body: '' });
  const [renaming, setRenaming] = useState(false);
  const [adding, setAdding] = useState(false);
  const last = messages.data.at(-1)?.id;

  // The latest in view as the conversation opens and as messages arrive (unless reading
  // further up)
  useLayoutEffect(() => {
    const element = scroller.current;
    if (element && atBottom.current) element.scrollTop = element.scrollHeight;
  }, [last, conversation.id]);

  const send = (event?: FormEvent) => {
    event?.preventDefault();
    if (!form.data.body.trim() || form.processing || heldOffline()) return;
    atBottom.current = true;
    form.post(route('chat.send', at), {
      preserveScroll: true,
      only: ['messages', 'conversations', 'project'],
      onSuccess: () => form.reset(),
    });
  };

  const earlier = () => {
    const element = scroller.current;
    const from = element ? element.scrollHeight - element.scrollTop : 0;
    atBottom.current = false;
    router.reload({
      data: { count: messages.data.length + MORE },
      only: ['messages'],
      onSuccess: () =>
        requestAnimationFrame(() => {
          // Where one was reading stays in place, the earlier ones above it
          if (element) element.scrollTop = element.scrollHeight - from;
        }),
    });
  };

  const remove = async (message: ChatMessage) => {
    if (
      await confirm({ title: _('Delete this message?'), confirmLabel: _('Delete'), danger: true })
    )
      router.delete(route('chat.messages.destroy', { project: project!.id, message: message.id }), {
        preserveScroll: true,
        only: ['messages', 'conversations'],
      });
  };

  const leave = async () => {
    if (
      await confirm({
        title: _('Leave {name}?', { name: conversation.title }),
        message: _('You will no longer see its messages.'),
        confirmLabel: _('Leave'),
        danger: true,
      })
    )
      router.delete(route('chat.leave', at));
  };

  return (
    <section className="chat-thread ds-surface" aria-label={conversation.title}>
      <header className="chat-thread-header">
        <Group gap="sm" wrap="nowrap" style={{ minWidth: 0 }}>
          <ActionIcon
            component={Link}
            href={route('chat.index', { project: project!.id })}
            variant="subtle"
            className="chat-back"
            aria-label={_('All conversations')}
          >
            <IconArrowLeft size={20} />
          </ActionIcon>
          <Faces conversation={conversation} />
          <div style={{ minWidth: 0 }}>
            <Text fw={700} truncate>
              {conversation.title}
            </Text>
            {conversation.is_group ? (
              <Text className="ds-caption" truncate>
                {conversation.people.map((person) => person.name).join(', ')}
              </Text>
            ) : null}
          </div>
        </Group>
        {conversation.can.update || conversation.can.leave ? (
          <Menu position="bottom-end">
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
      </header>

      <div
        ref={scroller}
        className="chat-messages"
        onScroll={(event) => {
          const element = event.currentTarget;
          atBottom.current = element.scrollHeight - element.scrollTop - element.clientHeight < 80;
        }}
      >
        {messages.earlier ? (
          <Group justify="center" mb="sm">
            <Button variant="subtle" size="xs" onClick={earlier}>
              {_('Earlier messages')}
            </Button>
          </Group>
        ) : null}
        {messages.data.length ? null : (
          <Text c="dimmed" ta="center" py="xl">
            {_('Say hello: the first message starts the conversation.')}
          </Text>
        )}
        {messages.data.map((message, index) => {
          const before = messages.data[index - 1];
          const newDay = !before || dayOf(before.created_at) !== dayOf(message.created_at);
          const sameAuthor = !newDay && before?.author?.id === message.author?.id;
          return (
            <div key={message.id}>
              {newDay ? <div className="chat-day">{dayLabel(message.created_at)}</div> : null}
              <div
                className="chat-message"
                data-mine={message.mine || undefined}
                data-follows={sameAuthor || undefined}
              >
                {!message.mine && conversation.is_group && !sameAuthor ? (
                  <Text className="chat-author">
                    {message.author?.name ?? _('Someone who left')}
                  </Text>
                ) : null}
                <div className="chat-bubble">
                  <Text component="div" className="chat-body">
                    {message.body}
                  </Text>
                  <span className="chat-time">
                    {new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(
                      new Date(message.created_at),
                    )}
                  </span>
                </div>
                {message.mine ? (
                  <ActionIcon
                    variant="subtle"
                    color="gray"
                    size="sm"
                    className="chat-delete"
                    aria-label={_('Delete this message')}
                    onClick={() => void remove(message)}
                  >
                    <IconTrash size={14} />
                  </ActionIcon>
                ) : null}
              </div>
            </div>
          );
        })}
      </div>

      <form className="chat-composer" onSubmit={send} data-tour="chat.composer">
        <Textarea
          aria-label={_('Message')}
          placeholder={_('Write a message')}
          autosize
          minRows={1}
          maxRows={6}
          style={{ flex: 1 }}
          value={form.data.body}
          error={form.errors.body}
          onChange={(event) => form.setData('body', event.currentTarget.value)}
          onKeyDown={(event) => {
            // Enter sends; Shift+Enter starts a new line
            if (event.key === 'Enter' && !event.shiftKey && !event.nativeEvent.isComposing) {
              event.preventDefault();
              send();
            }
          }}
        />
        <ActionIcon
          type="submit"
          size={42}
          radius="xl"
          variant="filled"
          loading={form.processing}
          disabled={!form.data.body.trim()}
          aria-label={_('Send')}
        >
          <IconSend2 size={20} />
        </ActionIcon>
      </form>

      <RenameGroup
        opened={renaming}
        onClose={() => setRenaming(false)}
        conversation={conversation}
      />
      <AddPeople
        opened={adding}
        onClose={() => setAdding(false)}
        conversation={conversation}
        people={people.filter((person) => !conversation.people.some((one) => one.id === person.id))}
      />
    </section>
  );
}

function RenameGroup({
  opened,
  onClose,
  conversation,
}: {
  opened: boolean;
  onClose: () => void;
  conversation: NonNullable<Props['conversation']>;
}) {
  const { project } = usePage().props;
  const form = useForm({ title: conversation.is_group ? conversation.title : '' });
  useEffect(() => form.setData('title', conversation.title), [conversation.id, opened]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <Modal opened={opened} onClose={onClose} title={_('Rename the group')}>
      <form
        onSubmit={(event) => {
          event.preventDefault();
          form.put(route('chat.update', { project: project!.id, conversation: conversation.id }), {
            preserveScroll: true,
            onSuccess: onClose,
          });
        }}
      >
        <TextInput
          label={_('Group name')}
          data-autofocus
          maxLength={80}
          value={form.data.title}
          onChange={(event) => form.setData('title', event.currentTarget.value)}
        />
        <Group justify="flex-end" mt="lg">
          <Button type="submit" loading={form.processing}>
            {_('Save')}
          </Button>
        </Group>
      </form>
    </Modal>
  );
}

function AddPeople({
  opened,
  onClose,
  conversation,
  people,
}: {
  opened: boolean;
  onClose: () => void;
  conversation: NonNullable<Props['conversation']>;
  people: ChatPerson[];
}) {
  const { project } = usePage().props;
  const form = useForm<{ title: string; people: string[] }>({
    title: conversation.title,
    people: [],
  });

  return (
    <Modal opened={opened} onClose={onClose} title={_('Add people')}>
      <form
        onSubmit={(event) => {
          event.preventDefault();
          form.transform((data) => ({
            title: conversation.title,
            people: data.people.map(Number),
          }));
          form.put(route('chat.update', { project: project!.id, conversation: conversation.id }), {
            preserveScroll: true,
            onSuccess: () => {
              form.reset();
              onClose();
            },
          });
        }}
      >
        <MultiSelect
          label={_('People')}
          data-autofocus
          searchable
          data={people.map((person) => ({ value: String(person.id), label: person.name }))}
          value={form.data.people}
          onChange={(value) => form.setData('people', value)}
          nothingFoundMessage={_('Nobody else in the project goes by that name')}
        />
        <Group justify="flex-end" mt="lg">
          <Button type="submit" loading={form.processing} disabled={!form.data.people.length}>
            {_('Add')}
          </Button>
        </Group>
      </form>
    </Modal>
  );
}

/** Starting a conversation: one person (theirs, if there is one already) or a group */
function NewConversation({
  opened,
  onClose,
  people,
}: {
  opened: boolean;
  onClose: () => void;
  people: ChatPerson[];
}) {
  const { project } = usePage().props;
  const form = useForm<{ people: string[]; title: string }>({ people: [], title: '' });
  const group = form.data.people.length > 1;

  return (
    <Modal opened={opened} onClose={onClose} title={_('New conversation')}>
      <form
        onSubmit={(event) => {
          event.preventDefault();
          form.transform((data) => ({
            people: data.people.map(Number),
            title: group ? data.title : null,
          }));
          form.post(route('chat.store', { project: project!.id }), {
            onSuccess: () => {
              form.reset();
              onClose();
            },
          });
        }}
      >
        <Stack>
          <MultiSelect
            label={_('With')}
            description={_('One person, or several for a group.')}
            data-autofocus
            searchable
            data={people.map((person) => ({ value: String(person.id), label: person.name }))}
            value={form.data.people}
            error={form.errors.people}
            onChange={(value) => form.setData('people', value)}
            nothingFoundMessage={_('Nobody else in the project goes by that name')}
          />
          {group ? (
            <TextInput
              label={_('Group name')}
              placeholder={_('Building committee, Block A…')}
              maxLength={80}
              value={form.data.title}
              onChange={(event) => form.setData('title', event.currentTarget.value)}
            />
          ) : null}
        </Stack>
        <Group justify="flex-end" mt="lg">
          <Button type="submit" loading={form.processing} disabled={!form.data.people.length}>
            {group ? _('Start the group') : _('Start the conversation')}
          </Button>
        </Group>
      </form>
    </Modal>
  );
}

/**
 * The project's chat: someone's conversations, one on one or in groups, and the one open.
 * On a phone, the list or the conversation, one at a time.
 */
export default function ChatIndex({ conversations, conversation, messages, people }: Props) {
  const [starting, setStarting] = useState(false);

  return (
    <>
      <PageHeader
        tour="member.chat"
        title={_('Messages')}
        description={_('Conversations with the people of the project, one on one or in groups.')}
        help={
          <InfoTip title={_('Messages')} tour="member.chat">
            {_(
              'Only those in a conversation read it: nobody else, the admins included. For what concerns everyone, the forum is the place.',
            )}
          </InfoTip>
        }
        actions={
          <Button
            data-tour="chat.new"
            leftSection={<IconPlus size={18} />}
            onClick={() => setStarting(true)}
          >
            {_('New conversation')}
          </Button>
        }
      />

      {conversations.length ? (
        <div className="chat" data-open={conversation ? true : undefined}>
          <ConversationList conversations={conversations} open={conversation?.id ?? null} />
          {conversation && messages ? (
            <Thread
              key={conversation.id}
              conversation={conversation}
              messages={messages}
              people={people}
            />
          ) : (
            <div className="chat-thread chat-none ds-surface">
              <Text c="dimmed">{_('Pick a conversation, or start a new one.')}</Text>
            </div>
          )}
        </div>
      ) : (
        <EmptyState icon={IconMessageCircle} title={_('No conversations yet')}>
          {_('Start one with anyone in the project, or a group.')}
        </EmptyState>
      )}

      <NewConversation opened={starting} onClose={() => setStarting(false)} people={people} />
    </>
  );
}

import { DateTime, formatDate } from '@/components/feedback/DateTime';
import { PersonAvatar } from '@/design';
import { minimizeChat, showConversation, useChat } from '@/lib/chat';
import { confirm } from '@/lib/dialogs';
import { fieldErrors, sendJson } from '@/lib/json';
import { heldOffline } from '@/lib/offline';
import { route } from '@/lib/route';
import { toast } from '@/lib/toasts';
import { startTour } from '@/lib/tour';
import { _ } from '@/lib/translations';
import { router, usePage } from '@inertiajs/react';
import {
  ActionIcon,
  Avatar,
  Button,
  Group,
  Menu,
  Modal,
  MultiSelect,
  SegmentedControl,
  Skeleton,
  Stack,
  Text,
  Textarea,
  TextInput,
} from '@mantine/core';
import {
  IconArrowLeft,
  IconArrowRight,
  IconDoorExit,
  IconDots,
  IconMinus,
  IconPencil,
  IconPin,
  IconPinFilled,
  IconPlus,
  IconSend2,
  IconTrash,
  IconUserPlus,
} from '@tabler/icons-react';
import {
  useCallback,
  useEffect,
  useLayoutEffect,
  useRef,
  useState,
  type FormEvent,
  type ReactNode,
} from 'react';

export interface ChatPerson {
  id: number;
  name: string;
  avatar: string | null;
}

interface ConversationSummary {
  id: number;
  title: string;
  is_group: boolean;
  is_community: boolean;
  is_assistant?: boolean;
  /** It was asked, and has not said anything yet */
  answering?: boolean;
  faces: ChatPerson[];
  last: { body: string; author: string | null; mine: boolean; at: string } | null;
  unread?: boolean;
  pinned?: boolean;
}

interface ChatMessage {
  id: number;
  body: string;
  author: ChatPerson | null;
  mine: boolean;
  from_assistant?: boolean;
  actions?: AssistantAction[];
  created_at: string;
}

/** A button under an Assistant's message: to a place (its tour too), or a reply sent with a tap */
interface AssistantAction {
  type: 'link' | 'tour' | 'reply';
  label: string;
  href?: string;
  tour?: string;
}

type OpenConversation = ConversationSummary & {
  people: ChatPerson[];
  can: { update: boolean; leave: boolean };
};

export interface ChatData {
  conversations: ConversationSummary[];
  conversation: OpenConversation | null;
  messages: { data: ChatMessage[]; earlier: boolean } | null;
  people: ChatPerson[];
}

/** A list this long gets a search */
const SEARCH_FROM = 12;

/** Earlier messages come this many at a time */
const MORE = 50;

/** What was last seen, per project: moving between pages, the window opens on it at once */
const seen = new Map<number, ChatData>();

const refused = () => toast({ tone: 'error', message: _('That did not work. Please try again.') });

/** The Assistant: a small friendly house, the app's subject, looking at you */
function AssistantFace({ size = 40 }: { size?: number }) {
  return (
    <svg className="assistant-face" viewBox="0 0 40 40" width={size} height={size} aria-hidden>
      <circle cx="20" cy="20" r="20" />
      <path
        className="assistant-house"
        d="M20 8.5 8.8 18.2c-.8.7-.3 2 .7 2H11v9.3c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-9.3h1.5c1 0 1.5-1.3.7-2z"
      />
      <circle className="assistant-eye" cx="16.3" cy="21.2" r="1.7" />
      <circle className="assistant-eye" cx="23.7" cy="21.2" r="1.7" />
      <path className="assistant-smile" d="M15.8 25.6c2.4 2.2 6 2.2 8.4 0" />
    </svg>
  );
}

/** A conversation's faces: the other person, or a group's first few, overlapping */
function Faces({ conversation }: { conversation: ConversationSummary }) {
  if (conversation.is_assistant) return <AssistantFace />;
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

/** Someone's conversations, the latest first, chats and groups apart; the unread marked */
function ConversationList({
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
  const projectId = usePage().props.project!.id;
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
    const url = route('chat.pin', { project: projectId, conversation: conversation.id });
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
            {popup ? <MinimizeButton /> : null}
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
          {_('No one-on-one chats yet.')}
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

function MinimizeButton() {
  return (
    <ActionIcon variant="subtle" color="gray" aria-label={_('Minimize')} onClick={minimizeChat}>
      <IconMinus size={18} />
    </ActionIcon>
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

/** Before the first message: what it is, and a few things to ask with a tap */
function AssistantWelcome({ onAsk }: { onAsk: (words: string) => void }) {
  const can = usePage().props.project?.can;
  const asks = [
    _('What can I do here?'),
    _('How does the lottery work?'),
    ...(can?.rank ? [_('How do I rank the units?')] : []),
    ...(can?.manage ? [_('What is left to set up?')] : []),
    ...(can?.invite && !can?.manage ? [_('How do I invite my family?')] : []),
  ];

  return (
    <div className="chat-message" data-assistant>
      <AssistantFace size={28} />
      <div className="chat-bubble">
        <Text component="div" className="chat-body">
          {_(
            'Hello! I am the Assistant of this app, an automatic helper. I can explain how things work and take you where you need to go. What would you like to know?',
          )}
        </Text>
      </div>
      <AssistantActions
        actions={asks.map((label) => ({ type: 'reply' as const, label }))}
        onReply={onAsk}
      />
    </div>
  );
}

/** The buttons under an Assistant's message; leading somewhere, the window folds out of the way on phones */
function AssistantActions({
  actions,
  onReply,
}: {
  actions: AssistantAction[];
  onReply: (words: string) => void;
}) {
  const press = (action: AssistantAction) => {
    if (action.type === 'reply') return onReply(action.label);

    router.visit(action.href!, {
      onSuccess: () => {
        if (action.tour) startTour(action.tour);
      },
    });
    return undefined;
  };

  return (
    <Group gap="xs" className="chat-actions">
      {actions.map((action) => (
        <Button
          key={`${action.type}-${action.label}`}
          size="xs"
          variant={action.type === 'reply' ? 'default' : 'light'}
          rightSection={action.type === 'reply' ? null : <IconArrowRight size={14} />}
          onClick={() => press(action)}
        >
          {action.label}
        </Button>
      ))}
    </Group>
  );
}

/** The open conversation: its messages, the latest at the bottom, and writing a new one */
function Thread({
  conversation,
  messages,
  people,
  popup,
  onChange,
  onEarlier,
}: {
  conversation: OpenConversation;
  messages: NonNullable<ChatData['messages']>;
  people: ChatPerson[];
  popup: boolean;
  onChange: () => Promise<void>;
  onEarlier: (count: number) => Promise<void>;
}) {
  const { project } = usePage().props;
  const at = { project: project!.id, conversation: conversation.id };
  const scroller = useRef<HTMLDivElement>(null);
  const atBottom = useRef(true);
  const [body, setBody] = useState('');
  const [sending, setSending] = useState(false);
  const [bodyError, setBodyError] = useState<string>();
  const [renaming, setRenaming] = useState(false);
  const [adding, setAdding] = useState(false);
  const last = messages.data.at(-1)?.id;

  // The latest in view as the conversation opens and as messages arrive (unless reading
  // further up)
  useLayoutEffect(() => {
    const element = scroller.current;
    if (element && atBottom.current) element.scrollTop = element.scrollHeight;
  }, [last, conversation.id]);

  const send = async (event?: FormEvent, words = body) => {
    event?.preventDefault();
    if (!words.trim() || sending || heldOffline()) return;
    atBottom.current = true;
    setSending(true);
    setBodyError(undefined);
    const response = await sendJson('POST', route('chat.send', at), { body: words });
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
    await onEarlier(messages.data.length + MORE);
    requestAnimationFrame(() => {
      // Where one was reading stays in place, the earlier ones above it
      if (element) element.scrollTop = element.scrollHeight - from;
    });
  };

  const remove = async (message: ChatMessage) => {
    if (
      await confirm({ title: _('Delete this message?'), confirmLabel: _('Delete'), danger: true })
    ) {
      const url = route('chat.messages.destroy', { project: project!.id, message: message.id });
      if ((await sendJson('DELETE', url, {})).ok) await onChange();
      else refused();
    }
  };

  const leave = async () => {
    if (
      await confirm({
        title: _('Leave {name}?', { name: conversation.title }),
        message: _('You will no longer see its messages.'),
        confirmLabel: _('Leave'),
        danger: true,
      })
    ) {
      if ((await sendJson('DELETE', route('chat.leave', at), {})).ok) {
        showConversation(null);
        await onChange();
      } else refused();
    }
  };

  return (
    <section className="chat-thread ds-surface" aria-label={conversation.title}>
      <header className="chat-thread-header">
        <Group gap="sm" wrap="nowrap" style={{ minWidth: 0 }}>
          <ActionIcon
            onClick={() => showConversation(null)}
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
          {popup ? <MinimizeButton /> : null}
        </Group>
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
            <Button variant="subtle" size="xs" onClick={() => void earlier()}>
              {_('Earlier messages')}
            </Button>
          </Group>
        ) : null}
        {messages.data.length || conversation.is_assistant ? null : (
          <Text c="dimmed" ta="center" py="xl">
            {_('Say hello: the first message starts the conversation.')}
          </Text>
        )}
        {!messages.data.length && conversation.is_assistant ? (
          <AssistantWelcome onAsk={(words) => void send(undefined, words)} />
        ) : null}
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
                {message.from_assistant && !sameAuthor ? <AssistantFace size={28} /> : null}
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
                {message.actions?.length ? (
                  <AssistantActions
                    actions={message.actions}
                    onReply={(words) => void send(undefined, words)}
                  />
                ) : null}
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

      {conversation.answering ? (
        <div className="chat-answering" role="status" aria-label={_('The Assistant is writing')}>
          <span />
          <span />
          <span />
        </div>
      ) : null}
      <form
        className="chat-composer"
        onSubmit={(event) => void send(event)}
        data-tour="chat.composer"
      >
        <Textarea
          aria-label={_('Message')}
          placeholder={_('Write a message')}
          autosize
          minRows={1}
          maxRows={6}
          style={{ flex: 1 }}
          value={body}
          error={bodyError}
          onChange={(event) => setBody(event.currentTarget.value)}
          onKeyDown={(event) => {
            // Enter sends; Shift+Enter starts a new line
            if (event.key === 'Enter' && !event.shiftKey && !event.nativeEvent.isComposing) {
              event.preventDefault();
              void send();
            }
          }}
        />
        <ActionIcon
          type="submit"
          size={42}
          radius="xl"
          variant="filled"
          loading={sending}
          disabled={!body.trim()}
          aria-label={_('Send')}
        >
          <IconSend2 size={20} />
        </ActionIcon>
      </form>

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
    </section>
  );
}

function RenameGroup({
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
  const { project } = usePage().props;
  const [title, setTitle] = useState(conversation.title);
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

function AddPeople({
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
  const { project } = usePage().props;
  const [chosen, setChosen] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string>();
  const picker = useRef<HTMLInputElement>(null);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    const url = route('chat.update', { project: project!.id, conversation: conversation.id });
    const response = await sendJson('PUT', url, {
      title: conversation.title,
      people: chosen.map(Number),
    });
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

/** Starting a conversation: one person (theirs, if there is one already) or a group */
function NewConversation({
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
  const { project } = usePage().props;
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

/**
 * The project's chat, the same in the window that opens from its bubble and on its page (where
 * phones have it): someone's conversations and the one open, one at a time where there is no
 * room for both. It never leaves the page it is on: it asks the server on its own.
 */
export function Chat({
  initial,
  popup = false,
}: {
  initial?: ChatData;
  popup?: boolean;
}): ReactNode {
  const projectId = usePage().props.project!.id;
  const { conversation: open, version } = useChat();
  const [data, setData] = useState<ChatData | null>(initial ?? seen.get(projectId) ?? null);
  // How many messages were asked for, of the conversation they were asked for
  const [asking, setAsking] = useState({ conversation: open, count: MORE });
  const count = asking.conversation === open ? asking.count : MORE;
  const [starting, setStarting] = useState(false);
  // The latest ask wins: a slow answer never replaces a newer one
  const asked = useRef(0);

  const load = useCallback(
    async (messages = count) => {
      const ask = ++asked.current;
      const url = route('chat.panel', {
        project: projectId,
        ...(open ? { conversation: open, count: messages } : {}),
      });
      const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      // Gone (they left it, or it was never theirs): back to the list
      if (response.status === 404 || response.status === 403) {
        showConversation(null);
        return;
      }
      if (!response.ok || ask !== asked.current) return;
      const next = (await response.json()) as ChatData;
      const wasUnread = seen
        .get(projectId)
        ?.conversations.some((one) => one.id === open && one.unread);
      seen.set(projectId, next);
      setData(next);
      // Read now: whatever counts the conversations with something new follows
      if (wasUnread) router.reload({ only: ['project'] });
    },
    [projectId, open, count],
  );

  useEffect(() => {
    void load();
  }, [load, version]);

  if (!data) {
    return (
      <div className="chat">
        <Skeleton h="100%" radius="lg" />
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
          onEarlier={async (more) => {
            setAsking({ conversation: open, count: more });
            await load(more);
          }}
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

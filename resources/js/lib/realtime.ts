import type { Notification } from '@/types/models';
import type { RealtimeServer } from '@/types/page';
import { http, router, usePage } from '@inertiajs/react';
import { configureEcho, echo } from '@laravel/echo-react';
import { useEffect, useEffectEvent } from 'react';
import { chatState, openChat, refreshChat, useChatProject } from './chat';
import { route, routeIs } from './route';
import { nameSocket, socketHeader } from './socketHeader';
import { createStore } from './store';
import { toast } from './toasts';
import { _ } from './translations';
import { unreadCount } from './unreadCount';

let server: RealtimeServer | null = null;

/** Connects to the websocket server the app was given, if any (live updates are off without) */
export function connectRealtime(given: RealtimeServer | null) {
  server = given;
  if (!server) return;
  configureEcho({
    broadcaster: 'reverb',
    key: server.key,
    wsHost: server.host,
    wsPort: server.port,
    wssPort: server.port,
    forceTLS: server.scheme === 'https',
    enabledTransports: ['ws', 'wss'],
  });
  // Where Laravel apps keep it, for inspecting the connection from the console (and
  // tests). Read on demand: the connection opens when something first listens, so guests
  // on the landing page hold none
  Object.defineProperty(window, 'Echo', { get: echo, configurable: true });
}

/** Live updates are on when a websocket server is configured */
export const realtimeEnabled = (): boolean => server !== null;

/** Whether the page hears what is said now: connected to the websocket server */
export const liveConnection = createStore<{ connected: boolean }>({ connected: false });

let following = false;

/**
 * Opens the connection, once, following it: back after a gap, what was said meanwhile never
 * came, so the page and its counts are asked for again, once. Each request then names this
 * page's connection (X-Socket-ID), so what it changes is announced to all but itself.
 */
export function openConnection() {
  if (following) return;
  following = true;
  const connector = echo().connector;

  let heard = false;
  const follow = (status: string) => {
    const connected = status === 'connected';
    if (connected === liveConnection.get().connected) return;
    liveConnection.set({ connected });
    if (!connected) return;
    if (heard) {
      router.reload();
      refreshChat();
    }
    heard = true;
  };
  follow(connector.connectionStatus());
  connector.onConnectionChange(follow);

  nameSocket(() => connector.socketId());
  http.onRequest((config) => ({ ...config, headers: { ...config.headers, ...socketHeader() } }));
}

/** How many listen on each private channel: the last to stop leaves it */
const listening = new Map<string, number>();

/**
 * Listens on a private channel, shared with whoever else in the page listens there, until
 * what it returns is called: only these handlers stop, and nobody listening means subscribed
 * no more.
 */
export function listenOn(name: string, handlers: Record<string, (data: never) => void>) {
  openConnection();
  const channel = echo().private(name);
  Object.entries(handlers).forEach(([event, handler]) => channel.listen(event, handler));
  listening.set(name, (listening.get(name) ?? 0) + 1);

  return () => {
    Object.entries(handlers).forEach(([event, handler]) => channel.stopListening(event, handler));
    const left = (listening.get(name) ?? 1) - 1;
    if (left) listening.set(name, left);
    else {
      listening.delete(name);
      echo().leaveChannel(`private-${name}`);
    }
  };
}

export interface ProjectChange {
  resource: string;
  action: 'created' | 'updated' | 'deleted';
  id: number;
  by: number | null;
}

/**
 * Keep the page in sync with its project: when one of the given resources changes (on
 * another page: someone else's, or this person's other tab or device), reload the page
 * props listed for it. The page that made the change is never told (the server leaves out
 * its connection).
 *
 *   useProjectChanges({ families: ['families'], people: ['families'] })
 */
export function useProjectChanges(reloads: Record<string, string[]>) {
  const { props } = usePage();
  const projectId = props.project?.id;
  const onChange = useEffectEvent((change: ProjectChange) => {
    const only = reloads[change.resource];
    if (only?.length) router.reload({ only });
  });

  useEffect(() => {
    if (!projectId || !realtimeEnabled()) return undefined;

    return listenOn(`projects.${projectId}`, { '.changed': onChange });
  }, [projectId]);
}

/** How often an open lottery's page asks, while it cannot be told */
export const OPEN_LOTTERY_EVERY_MS = 15000;

/**
 * Keep an open lottery's page current: at once when told, and by asking now and then only
 * while it cannot be (its connection lost, or a server with none).
 */
export function useOpenLotteryChanges(slug: string) {
  useEffect(() => {
    const reload = () => router.reload();
    let every: number | undefined;
    const askWhileDeaf = () => {
      const deaf = !realtimeEnabled() || !liveConnection.get().connected;
      if (deaf && every === undefined) every = window.setInterval(reload, OPEN_LOTTERY_EVERY_MS);
      if (!deaf && every !== undefined) {
        window.clearInterval(every);
        every = undefined;
      }
    };

    let stopFollowing: () => void = () => undefined;
    if (realtimeEnabled()) {
      openConnection();
      echo().channel(`open-lottery.${slug}`).listen('.changed', reload);
      stopFollowing = liveConnection.subscribe(askWhileDeaf);
    }
    askWhileDeaf();

    return () => {
      window.clearInterval(every);
      stopFollowing();
      if (realtimeEnabled()) echo().leaveChannel(`open-lottery.${slug}`);
    };
  }, [slug]);
}

/** Show notifications as they arrive (the shell, once), and keep the unread counter current */
export function useLiveNotifications() {
  const { props } = usePage();
  const user = props.auth.user;
  const projectId = props.project?.id;

  useEffect(() => {
    if (!user || !realtimeEnabled()) return undefined;

    const channels = [`users.${user.id}`];
    if (projectId) channels.push(`projects.${projectId}`);
    if (user.is_superadmin) channels.push('superadmins');

    // Only which one arrives: it is fetched in this person's language, then shown
    const onNotification = ({ id }: { id: number }) =>
      router.reload({
        only: ['notifications', 'recentNotifications'],
        onSuccess: (page) => {
          const recent = page.props.recentNotifications as
            { data: Notification[] } | Notification[] | undefined;
          const notification = (Array.isArray(recent) ? recent : recent?.data)?.find(
            (one) => one.id === id,
          );
          if (!notification) return;
          toast({
            tone: 'info',
            live: true,
            title: notification.title,
            message: notification.message,
            href: notification.url,
          });
        },
      });

    const stops = channels.map((name) => listenOn(name, { '.notification': onNotification }));

    return () => stops.forEach((stop) => stop());
  }, [user?.id, user?.is_superadmin, projectId]); // eslint-disable-line react-hooks/exhaustive-deps
}

interface MessageArrived {
  conversation_id: number;
  message_id: number;
  by: number | null;
  author: string | null;
  from_assistant: boolean;
}

interface MessageChanged {
  conversation_id: number;
  by: number | null;
  /** Deleted, rather than edited */
  removed: boolean;
}

/** The chat's list of conversations, asked for a moment apart by each of those it moves for */
const LIST_SPREAD_MS = 3000;
let listAsked: number | undefined;
const refreshListSoon = () => {
  listAsked ??= window.setTimeout(() => {
    listAsked = undefined;
    refreshChat();
  }, Math.random() * LIST_SPREAD_MS);
};

/** The count of the unread, asked for a moment apart likewise */
let countAsked: number | undefined;
const refreshCountSoon = (prop: string) => {
  countAsked ??= window.setTimeout(() => {
    countAsked = undefined;
    router.reload({ only: [prop] });
  }, Math.random() * LIST_SPREAD_MS);
};

const unread = unreadCount();

/**
 * Chat messages as they arrive (the shell, once): on the chat's page, its conversations
 * and the one open (read as it shows); anywhere else, the chat's count of the unread, and
 * a toast saying who wrote. In a busy group nobody asks the server more than what they
 * see needs: the conversation in sight at once, the rest a moment later, and the count
 * only if it can move.
 */
export function useLiveMessages() {
  const user = usePage().props.auth.user;
  // Whichever chat the page holds: the project's, one framing the person's own pages, or theirs
  const { project, account, prop } = useChatProject();
  const chatting = Boolean(project?.can.chat || account);
  const chatKey = project?.id ?? 0;
  const onMessage = useEffectEvent((message: MessageArrived) => {
    if (message.by === user?.id) return;

    const { open, conversation } = chatState();
    const inChat = open || routeIs('chat.*', 'account.chat.*');

    if (inChat && conversation === message.conversation_id) {
      unread.inSight(message.conversation_id);
      refreshChat();
      return;
    }

    refreshListSoon();
    if (unread.arrives(message.conversation_id)) router.reload({ only: [prop] });

    // Already in sight: the list it comes to
    if (inChat && !conversation) return;

    // The window opens on it where there is one; on phones the chat has its own page
    const withWindow = window.matchMedia('(min-width: 48em)').matches;
    toast({
      tone: 'info',
      live: true,
      message:
        message.author || message.from_assistant
          ? _('New message from {name}', { name: message.author ?? _('Assistant') })
          : _('New message'),
      onClick: withWindow ? () => openChat(message.conversation_id) : undefined,
      href: withWindow
        ? null
        : project
          ? route('chat.show', { project: project.id, conversation: message.conversation_id })
          : route('account.chat.index'),
    });
  });

  // A message edited or deleted (on any page, the writer's others too): what shows it asks
  // again, the conversation in sight at once; deleted, it may have been what was unread
  const onChanged = useEffectEvent((changed: MessageChanged) => {
    const { open, conversation } = chatState();
    const inChat = open || routeIs('chat.*', 'account.chat.*');

    if (inChat && conversation === changed.conversation_id) return refreshChat();
    if (inChat) return refreshListSoon();
    if (changed.removed && (known ?? 0) > 0) refreshCountSoon(prop);
    return undefined;
  });

  const known = project ? project.unread_messages : account?.unread;
  useEffect(() => unread.reset, [chatKey]);
  useEffect(() => unread.known(known), [known]);

  useEffect(() => {
    if (!user || !chatting || !realtimeEnabled()) return undefined;
    return listenOn(`users.${user.id}`, { '.message': onMessage, '.message-changed': onChanged });
  }, [user?.id, chatKey, chatting]); // eslint-disable-line react-hooks/exhaustive-deps
}

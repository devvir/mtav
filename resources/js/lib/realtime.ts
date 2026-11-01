import type { Notification } from '@/types/models';
import type { RealtimeServer } from '@/types/page';
import { router, usePage } from '@inertiajs/react';
import { configureEcho, echo } from '@laravel/echo-react';
import { useEffect, useEffectEvent } from 'react';
import { chatState, openChat, refreshChat } from './chat';
import { route, routeIs } from './route';
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

/** How many listen on each private channel: the last to stop leaves it */
const listening = new Map<string, number>();

/**
 * Listens on a private channel, shared with whoever else in the page listens there, until
 * what it returns is called: only these handlers stop, and nobody listening means subscribed
 * no more.
 */
export function listenOn(name: string, handlers: Record<string, (data: never) => void>) {
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
 * Keep the page in sync with its project: when one of the given resources changes (by
 * someone else), reload the page props listed for it.
 *
 *   useProjectChanges({ families: ['families'], people: ['families'] })
 */
export function useProjectChanges(reloads: Record<string, string[]>) {
  const { props } = usePage();
  const projectId = props.project?.id;
  const userId = props.auth.user?.id;
  const onChange = useEffectEvent((change: ProjectChange) => {
    const only = reloads[change.resource];
    if (only?.length && change.by !== userId) router.reload({ only });
  });

  useEffect(() => {
    if (!projectId || !realtimeEnabled()) return undefined;

    return listenOn(`projects.${projectId}`, { '.changed': onChange });
  }, [projectId, userId]);
}

/**
 * Keep an open lottery's page current: at once when told, and by asking now and then, for a page
 * that lost its connection (or a server with none).
 */
export function useOpenLotteryChanges(slug: string) {
  useEffect(() => {
    const reload = () => router.reload();
    const every = window.setInterval(reload, 15000);

    if (realtimeEnabled()) echo().channel(`open-lottery.${slug}`).listen('.changed', reload);

    return () => {
      window.clearInterval(every);
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

interface MessageRemoved {
  conversation_id: number;
  by: number | null;
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

const unread = unreadCount();

/**
 * Chat messages as they arrive (the shell, once): on the chat's page, its conversations
 * and the one open (read as it shows); anywhere else, the chat's count of the unread, and
 * a toast saying who wrote. In a busy group nobody asks the server more than what they
 * see needs: the conversation in sight at once, the rest a moment later, and the count
 * only if it can move.
 */
export function useLiveMessages() {
  const { props } = usePage();
  const user = props.auth.user;
  const project = props.project;
  const onMessage = useEffectEvent((message: MessageArrived) => {
    if (message.by === user?.id) return;

    const { open, conversation } = chatState();
    const inChat = open || routeIs('chat.*');

    if (inChat && conversation === message.conversation_id) {
      unread.inSight(message.conversation_id);
      refreshChat();
      return;
    }

    refreshListSoon();
    if (unread.arrives(message.conversation_id)) router.reload({ only: ['project'] });

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
      href:
        !withWindow && project
          ? route('chat.show', { project: project.id, conversation: message.conversation_id })
          : null,
    });
  });

  // A message taken back: what shows it asks again, and the count may have moved
  const onRemoved = useEffectEvent((removed: MessageRemoved) => {
    if (removed.by === user?.id) return;
    refreshChat();
    router.reload({ only: ['project'] });
  });

  useEffect(() => unread.reset, [project?.id]);
  useEffect(() => unread.known(project?.unread_messages), [project?.unread_messages]);

  useEffect(() => {
    if (!user || !project?.can.chat || !realtimeEnabled()) return undefined;
    return listenOn(`users.${user.id}`, { '.message': onMessage, '.message-removed': onRemoved });
  }, [user?.id, project?.id, project?.can.chat]); // eslint-disable-line react-hooks/exhaustive-deps
}

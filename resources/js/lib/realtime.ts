import type { Notification } from '@/types/models';
import type { RealtimeServer } from '@/types/page';
import { router, usePage } from '@inertiajs/react';
import { configureEcho, echo } from '@laravel/echo-react';
import { useEffect, useEffectEvent } from 'react';
import { chatState, openChat, refreshChat } from './chat';
import { route, routeIs } from './route';
import { toast } from './toasts';
import { _ } from './translations';

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

    const channel = `projects.${projectId}`;
    echo().private(channel).listen('.changed', onChange);

    return () => {
      echo().private(channel).stopListening('.changed');
    };
  }, [projectId, userId]);
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

    channels.forEach((name) => echo().private(name).listen('.notification', onNotification));

    return () => {
      channels.forEach((name) => echo().private(name).stopListening('.notification'));
    };
  }, [user?.id, user?.is_superadmin, projectId]); // eslint-disable-line react-hooks/exhaustive-deps
}

interface MessageArrived {
  conversation_id: number;
  message_id: number;
  by: number | null;
  author: string | null;
}

/**
 * Chat messages as they arrive (the shell, once): on the chat's page, its conversations
 * and the one open (read as it shows); anywhere else, the chat's count of the unread, and
 * a toast saying who wrote.
 */
export function useLiveMessages() {
  const { props } = usePage();
  const user = props.auth.user;
  const project = props.project;
  const onMessage = useEffectEvent((message: MessageArrived) => {
    if (message.by === user?.id) return;
    refreshChat();
    router.reload({ only: ['project'] });

    // Already in sight: the chat shows it, or the list it comes to
    const { open, conversation } = chatState();
    const inChat = open || routeIs('chat.*');
    if (inChat && (!conversation || conversation === message.conversation_id)) return;

    // The window opens on it where there is one; on phones the chat has its own page
    const withWindow = window.matchMedia('(min-width: 48em)').matches;
    toast({
      tone: 'info',
      live: true,
      message: message.author
        ? _('New message from {name}', { name: message.author })
        : _('New message'),
      onClick: withWindow ? () => openChat(message.conversation_id) : undefined,
      href:
        !withWindow && project
          ? route('chat.show', { project: project.id, conversation: message.conversation_id })
          : null,
    });
  });

  useEffect(() => {
    if (!user || !project?.can.chat || !realtimeEnabled()) return undefined;
    const channel = `users.${user.id}`;
    echo().private(channel).listen('.message', onMessage);

    return () => {
      echo().private(channel).stopListening('.message');
    };
  }, [user?.id, project?.id, project?.can.chat]); // eslint-disable-line react-hooks/exhaustive-deps
}

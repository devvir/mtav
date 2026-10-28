import { router, usePage } from '@inertiajs/react';
import { echo } from '@laravel/echo-react';
import { useEffect, useEffectEvent } from 'react';
import { toast } from './toasts';

/** Live updates are on when a websocket server is configured (see app.tsx) */
export const realtimeEnabled = (): boolean => Boolean(import.meta.env.VITE_REVERB_APP_KEY);

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

    const onNotification = (notification: {
      title: string;
      message: string;
      url: string | null;
    }) => {
      toast({
        tone: 'info',
        live: true,
        title: notification.title,
        message: notification.message,
        href: notification.url,
      });
      router.reload({ only: ['notifications'] });
    };

    channels.forEach((name) => echo().private(name).listen('.notification', onNotification));

    return () => {
      channels.forEach((name) => echo().private(name).stopListening('.notification'));
    };
  }, [user?.id, user?.is_superadmin, projectId]); // eslint-disable-line react-hooks/exhaustive-deps
}

import { router, usePage } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import { onBeforeUnmount, onMounted } from 'vue';
import { useToasts } from './useToasts';

/** Live updates are on when a websocket server is configured (see app.ts) */
export const realtimeEnabled = (): boolean => Boolean(import.meta.env.VITE_REVERB_APP_KEY);

export interface ProjectChange {
  resource: string;
  action: 'created' | 'updated' | 'deleted';
  id: number;
  by: number | null;
}

/**
 * Keep the current page in sync with its project: when one of the given resources
 * changes (by someone else), reload the page props listed for it.
 *
 *   useProjectChanges({ families: ['families'], people: ['families'] })
 */
export function useProjectChanges(reloads: Record<string, string[]>) {
  const page = usePage();
  let channel: string | null = null;

  onMounted(() => {
    const project = page.props.project;
    if (!project || !realtimeEnabled()) return;

    channel = `projects.${project.id}`;
    echo()
      .private(channel)
      .listen('.changed', (change: ProjectChange) => {
        const only = reloads[change.resource];
        if (only?.length && change.by !== page.props.auth.user?.id) {
          router.reload({ only });
        }
      });
  });

  onBeforeUnmount(() => {
    if (channel) echo().private(channel).stopListening('.changed');
  });
}

/**
 * Show notifications as they arrive (mounted once by the layouts), and keep the
 * unread counter up to date.
 */
export function useLiveNotifications() {
  const page = usePage();
  const { show } = useToasts();
  const channels: string[] = [];

  const onNotification = (notification: { title: string; message: string; url: string | null }) => {
    show({
      tone: 'info',
      title: notification.title,
      message: notification.message,
      url: notification.url,
    });
    router.reload({ only: ['notifications'] });
  };

  onMounted(() => {
    const user = page.props.auth.user;
    if (!user || !realtimeEnabled()) return;

    channels.push(`users.${user.id}`);
    if (page.props.project) channels.push(`projects.${page.props.project.id}`);
    if (user.is_superadmin) channels.push('superadmins');

    channels.forEach((name) => echo().private(name).listen('.notification', onNotification));
  });

  onBeforeUnmount(() => {
    channels.forEach((name) => echo().private(name).stopListening('.notification'));
  });
}

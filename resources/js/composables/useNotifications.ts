// Copilot - Pending review
import { useEcho } from '@/composables/useEcho';

export type Notification = {
  id: string;
  message: string;
  timestamp: Date;
  read: boolean;
  channel: string;
  data?: Record<string, any>;
};

const notifications = ref<Notification[]>([]);
const unreadCount = computed(() => notifications.value.filter((n: Notification) => !n.read).length);

/**
 * Composable for managing real-time notifications.
 * Provides notification state, methods for adding/marking notifications, and auto-listens to channels.
 */
export function useNotifications() {
  const addNotification = (
    message: string,
    channel: string,
    data?: Record<string, any>,
  ): Notification => {
    const notification: Notification = {
      id: `${Date.now()}-${Math.random()}`,
      message,
      timestamp: new Date(),
      read: false,
      channel,
      data,
    };

    notifications.value.unshift(notification);

    // Keep only last 50 notifications
    if (notifications.value.length > 50) {
      notifications.value = notifications.value.slice(0, 50);
    }

    return notification;
  };

  const markAsRead = (id: string) => {
    const notification = notifications.value.find((n: Notification) => n.id === id);
    if (notification) {
      notification.read = true;
    }
  };

  const markAllAsRead = () => {
    notifications.value.forEach((n: Notification) => {
      n.read = true;
    });
  };

  const clearAll = () => {
    notifications.value = [];
  };

  const removeNotification = (id: string) => {
    notifications.value = notifications.value.filter((n: Notification) => n.id !== id);
  };

  return {
    notifications: readonly(notifications),
    unreadCount: readonly(unreadCount),
    addNotification,
    markAsRead,
    markAllAsRead,
    clearAll,
    removeNotification,
  };
}

/**
 * Setup listener for ResourceCreated events on a specific channel.
 * Automatically adds notifications when events are received.
 */
export function useNotificationsListener(channel: string) {
  const { addNotification } = useNotifications();
  const { listen } = useEcho();

  onMounted(() => {
    const cleanup = listen(channel, 'ResourceCreated', (event: any) => {
      addNotification(event.message, channel, event);
    });

    onUnmounted(() => {
      cleanup();
    });
  });
}

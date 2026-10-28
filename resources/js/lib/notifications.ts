import type { Notification } from '@/types/models';
import { router } from '@inertiajs/react';
import { route } from './route';

/**
 * Opens what a notification is about. An unread one is marked read on the way: the server
 * answers with what it is about, in one visit (two at once would cancel each other).
 */
export const openNotification = (notification: Notification) => {
  if (notification.read) {
    if (notification.url) router.visit(notification.url);
    return;
  }
  router.post(
    route('notifications.read', notification.id),
    {},
    { preserveScroll: !notification.url },
  );
};

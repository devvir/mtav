import { DateTime } from '@/components/feedback/DateTime';
import { openNotification } from '@/lib/notifications';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Notification } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import {
  ActionIcon,
  Anchor,
  Button,
  Group,
  Indicator,
  Loader,
  Popover,
  ScrollArea,
  Text,
  UnstyledButton,
} from '@mantine/core';
import { IconBell, IconBellOff, IconChecks } from '@tabler/icons-react';
import { useState } from 'react';

/**
 * The bell: how many are unread, and on a click the latest few, each opening what it is
 * about; the whole list is one link away.
 */
export function NotificationsMenu() {
  const { notifications, recentNotifications } = usePage().props as {
    notifications: { unread: number } | null;
    recentNotifications?: { data: Notification[] } | Notification[];
  };
  const unread = notifications?.unread ?? 0;
  const [opened, setOpened] = useState(false);
  const [loading, setLoading] = useState(false);
  const recent = Array.isArray(recentNotifications)
    ? recentNotifications
    : recentNotifications?.data;

  const load = () => {
    setLoading(true);
    router.reload({
      only: ['recentNotifications', 'notifications'],
      onFinish: () => setLoading(false),
    });
  };

  const open = (notification: Notification) => {
    setOpened(false);
    openNotification(notification);
  };

  // The count sits outside the popover, so that what the popover marks as opening it is the button
  return (
    <Indicator
      label={unread > 99 ? '99+' : unread}
      size={18}
      disabled={!unread}
      offset={7}
      color="red"
    >
      <Popover
        opened={opened}
        onChange={setOpened}
        position="bottom-end"
        width={380}
        onOpen={load}
        trapFocus
        returnFocus
      >
        <Popover.Target>
          <ActionIcon
            size="xl"
            radius="xl"
            variant="subtle"
            color="gray"
            aria-label={
              unread ? _('Notifications, {count} unread', { count: unread }) : _('Notifications')
            }
            onClick={() => setOpened((current) => !current)}
          >
            <IconBell size={22} stroke={1.8} />
          </ActionIcon>
        </Popover.Target>
        <Popover.Dropdown p={0} aria-label={_('Notifications')}>
          <Group justify="space-between" px="md" py="sm" className="bell-head">
            <Text fw={700}>{_('Notifications')}</Text>
            {unread ? (
              <Button
                variant="subtle"
                size="compact-sm"
                leftSection={<IconChecks size={16} />}
                onClick={() =>
                  router.post(
                    route('notifications.readAll'),
                    {},
                    { preserveScroll: true, onSuccess: load },
                  )
                }
              >
                {_('Mark all as read')}
              </Button>
            ) : null}
          </Group>
          <ScrollArea.Autosize mah={420}>
            {recent === undefined || (loading && !recent.length) ? (
              <Group justify="center" py="lg">
                <Loader size="sm" />
              </Group>
            ) : recent.length ? (
              <ul className="bell-list">
                {recent.map((notification) => (
                  <li key={notification.id}>
                    <UnstyledButton
                      className="notification"
                      data-unread={notification.read ? undefined : true}
                      onClick={() => open(notification)}
                    >
                      <span className="notification-dot" aria-hidden />
                      <span style={{ flex: 1, minWidth: 0 }}>
                        <Text fw={650} size="sm">
                          {notification.title}
                        </Text>
                        <Text size="sm" c="dimmed" lineClamp={2}>
                          {notification.message}
                        </Text>
                        <Text className="ds-caption" mt={2}>
                          <DateTime value={notification.created_at} relative />
                        </Text>
                      </span>
                    </UnstyledButton>
                  </li>
                ))}
              </ul>
            ) : (
              <Group justify="center" gap="xs" py="xl" c="dimmed">
                <IconBellOff size={20} /> {_('No notifications yet')}
              </Group>
            )}
          </ScrollArea.Autosize>
          <div className="bell-foot">
            <Anchor
              component={Link}
              href={route('notifications.index')}
              fw={650}
              size="sm"
              onClick={() => setOpened(false)}
            >
              {_('See all notifications')}
            </Anchor>
          </div>
        </Popover.Dropdown>
      </Popover>
    </Indicator>
  );
}

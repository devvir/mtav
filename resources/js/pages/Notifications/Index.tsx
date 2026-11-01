import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { openNotification } from '@/lib/notifications';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Notification, Paginated } from '@/types/models';
import { router } from '@inertiajs/react';
import { Button, Card, Text, UnstyledButton, VisuallyHidden } from '@mantine/core';
import { IconBellOff, IconChecks } from '@tabler/icons-react';

interface Props {
  notifications: Paginated<Notification>;
  filters: { unread: boolean };
}

export default function NotificationsIndex({ notifications, filters }: Props) {
  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Notifications')} tour="account.notifications">
            {_(
              'What happened in your projects, newest first. The bell shows the latest. Open one to go there.',
            )}
          </InfoTip>
        }
        title={_('Notifications')}
        tour="account.notifications"
        actions={
          <>
            <Button
              variant="default"
              data-tour="notifications.unread"
              aria-pressed={filters.unread}
              onClick={() =>
                router.get(route('notifications.index'), filters.unread ? {} : { unread: 1 }, {
                  preserveState: true,
                })
              }
            >
              {filters.unread ? _('Show all') : _('Only unread')}
            </Button>
            <Button
              variant="default"
              leftSection={<IconChecks size={18} />}
              onClick={() =>
                router.post(route('notifications.readAll'), {}, { preserveScroll: true })
              }
            >
              {_('Mark all as read')}
            </Button>
          </>
        }
      />

      {notifications.data.length ? (
        <Card p={0} data-tour="notifications.list">
          <ul className="notification-list">
            {notifications.data.map((notification) => (
              <li key={notification.id}>
                <UnstyledButton
                  className="notification"
                  data-unread={notification.read ? undefined : true}
                  aria-labelledby={`notification-${notification.id} notification-${notification.id}-read`}
                  aria-describedby={`notification-${notification.id}-about`}
                  onClick={() => openNotification(notification)}
                >
                  <span className="notification-dot" aria-hidden />
                  <span style={{ flex: 1, minWidth: 0 }}>
                    <Text
                      component="span"
                      display="block"
                      fw={700}
                      id={`notification-${notification.id}`}
                    >
                      {notification.title}
                    </Text>
                    <span id={`notification-${notification.id}-about`}>
                      <Text component="span" display="block" c="dimmed">
                        {notification.message}
                      </Text>
                      <Text component="span" display="block" className="ds-caption" mt={4}>
                        <DateTime value={notification.created_at} relative />
                      </Text>
                    </span>
                  </span>
                  <VisuallyHidden id={`notification-${notification.id}-read`}>
                    {notification.read ? _('Read') : _('Unread')}
                  </VisuallyHidden>
                </UnstyledButton>
              </li>
            ))}
          </ul>
        </Card>
      ) : (
        <EmptyState
          icon={IconBellOff}
          title={filters.unread ? _('Nothing unread') : _('No notifications yet')}
        />
      )}
      <Pagination page={notifications} />
    </>
  );
}

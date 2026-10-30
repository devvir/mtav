import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar, Status } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { toast } from '@/lib/toasts';
import { _, _n } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { router, usePage } from '@inertiajs/react';
import { ActionIcon, Card, Group, Menu, Text } from '@mantine/core';
import { IconDots } from '@tabler/icons-react';

interface Row {
  id: number;
  name: string;
  email: string;
  is_superadmin: boolean;
  blocked_at: string | null;
  verified: boolean;
  projects_count: number;
  created_at: string;
}

/** What a superadmin may do about an account: not their own, nor another superadmin's */
function AccountActions({ user }: { user: Row }) {
  const { auth } = usePage().props;
  if (user.is_superadmin || user.id === auth.user?.id) return null;

  const options = {
    preserveScroll: true,
    onError: (errors: Record<string, string>) =>
      toast({ tone: 'error', message: errors.account ?? Object.values(errors)[0] }),
  };

  const block = async () => {
    const ok = await confirm({
      title: _('Block {name}?', { name: user.name }),
      message: _(
        'They leave every project at once and cannot sign in. Their email stays taken, so they cannot join again.',
      ),
      confirmLabel: _('Block'),
      danger: true,
    });
    if (ok) router.put(route('admin.users.block', user.id), { block: true }, options);
  };

  const remove = async () => {
    const ok = await confirm({
      title: _('Delete the account of {name}?', { name: user.name }),
      message: _('It goes for good. Nothing stops them signing up again with the same email.'),
      confirmLabel: _('Delete'),
      danger: true,
    });
    if (ok) router.delete(route('admin.users.destroy', user.id), options);
  };

  return (
    <Menu position="bottom-end" width={200}>
      <Menu.Target>
        <ActionIcon
          variant="subtle"
          color="gray"
          aria-label={_('Actions for {name}', { name: user.name })}
        >
          <IconDots size={18} />
        </ActionIcon>
      </Menu.Target>
      <Menu.Dropdown>
        {user.blocked_at ? (
          <Menu.Item
            onClick={() =>
              router.put(route('admin.users.block', user.id), { block: false }, options)
            }
          >
            {_('Unblock')}
          </Menu.Item>
        ) : (
          <Menu.Item onClick={block}>{_('Block')}</Menu.Item>
        )}
        <Menu.Item color="red" onClick={remove}>
          {_('Delete')}
        </Menu.Item>
      </Menu.Dropdown>
    </Menu>
  );
}

export default function PlatformUsers({
  users,
  filters,
}: {
  users: Paginated<Row>;
  filters: { q: string | null; superadmins: boolean };
}) {
  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Accounts')} tour="platform.users">
            {_('Everyone with an account on the platform; blocking or deleting one is done here.')}
          </InfoTip>
        }
        title={_('Accounts')}
        tour="platform.users"
        description={_('Everyone with an account on the platform.')}
      />
      <div style={{ marginBottom: 16 }} data-tour="users.search">
        <QuerySearch value={filters.q} placeholder={_('Search by name or email')} maw={440} />
      </div>
      <Card p={0} data-tour="users.list">
        {users.data.map((user) => (
          <Group key={user.id} className="row" gap="md" wrap="wrap">
            <PersonAvatar name={user.name} size={40} />
            <div style={{ flex: 1, minWidth: 220 }}>
              <Group gap="sm">
                <Text fw={700}>{user.name}</Text>
                {user.is_superadmin ? (
                  <Status tone="info" pill>
                    {_('Superadmin')}
                  </Status>
                ) : null}
                {user.blocked_at ? (
                  <Status tone="negative" pill>
                    {_('Blocked')}
                  </Status>
                ) : null}
                {!user.verified ? (
                  <Status tone="warning" pill>
                    {_('Email not confirmed')}
                  </Status>
                ) : null}
              </Group>
              <Text className="ds-caption ds-truncate">
                {user.email} · {_n('{count} project', '{count} projects', user.projects_count)} ·{' '}
                <DateTime value={user.created_at} time={false} />
              </Text>
            </div>
            <AccountActions user={user} />
          </Group>
        ))}
      </Card>
      <Pagination page={users} />
    </>
  );
}

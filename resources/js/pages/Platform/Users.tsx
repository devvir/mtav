import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar, Status } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { router, usePage } from '@inertiajs/react';
import { Button, Card, Group, Text } from '@mantine/core';

interface Row {
  id: number;
  name: string;
  email: string;
  is_superadmin: boolean;
  verified: boolean;
  projects_count: number;
  created_at: string;
}

export default function PlatformUsers({
  users,
  filters,
}: {
  users: Paginated<Row>;
  filters: { q: string | null; superadmins: boolean };
}) {
  const me = usePage().props.auth.user?.id;

  const toggle = async (user: Row) => {
    const grant = !user.is_superadmin;
    const ok = await confirm({
      title: grant
        ? _('Make {name} a superadmin?', { name: user.name })
        : _("Remove {name}'s superadmin role?", { name: user.name }),
      message: grant ? _('Superadmins see and manage every project on the platform.') : undefined,
      confirmLabel: grant ? _('Make superadmin') : _('Remove the role'),
      danger: !grant,
    });
    if (ok)
      router.put(route('admin.users.superadmin', user.id), { grant }, { preserveScroll: true });
  };

  return (
    <>
      <PageHeader
        title={_('Accounts')}
        description={_('Everyone with an account on the platform.')}
      />
      <div style={{ marginBottom: 16 }}>
        <QuerySearch value={filters.q} placeholder={_('Search by name or email')} maw={440} />
      </div>
      <Card p={0}>
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
            {user.id !== me ? (
              <Button variant="default" size="sm" onClick={() => toggle(user)}>
                {user.is_superadmin ? _('Remove superadmin') : _('Make superadmin')}
              </Button>
            ) : null}
          </Group>
        ))}
      </Card>
      <Pagination page={users} />
    </>
  );
}

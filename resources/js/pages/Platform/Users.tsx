import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar, Status } from '@/design';
import { _, _n } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { Card, Group, Text } from '@mantine/core';

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
  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Accounts')} tour="platform.users">
            {_('Everyone with an account on the platform.')}
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
          </Group>
        ))}
      </Card>
      <Pagination page={users} />
    </>
  );
}

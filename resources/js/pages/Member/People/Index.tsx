import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar } from '@/design';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated, Person } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';
import { Badge, Button, Card, Group, SimpleGrid, Text } from '@mantine/core';
import { IconMail } from '@tabler/icons-react';

export default function PeopleIndex({
  people,
  filters,
}: {
  people: Paginated<Person>;
  filters: { q: string | null };
}) {
  useProjectChanges({ people: ['people'] });
  const canManage = usePage().props.project!.can.manage;

  return (
    <>
      <PageHeader
        title={_('People')}
        description={_('Everyone taking part in the project.')}
        actions={
          canManage ? (
            <Button
              component={Link}
              href={route('manage.invitations.index')}
              leftSection={<IconMail size={18} />}
            >
              {_('Invite people')}
            </Button>
          ) : null
        }
      />
      <div style={{ marginBottom: 16 }}>
        <QuerySearch value={filters.q} placeholder={_('Search by name')} maw={440} />
      </div>
      <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }}>
        {people.data.map((person) => (
          <Card
            key={person.id}
            component={Link}
            href={route('people.show', person.id)}
            p="sm"
            className="ds-interactive"
          >
            <Group wrap="nowrap">
              <PersonAvatar name={person.name} src={person.avatar} size={44} />
              <div style={{ minWidth: 0, flex: 1 }}>
                <Text fw={650} className="ds-truncate">
                  {person.name}
                </Text>
                <Text className="ds-caption ds-truncate">
                  {person.family?.name ?? _('No family')}
                </Text>
              </div>
              {person.is_admin ? <Badge color="blue">{_('Admin')}</Badge> : null}
            </Group>
          </Card>
        ))}
      </SimpleGrid>
      {!people.data.length ? (
        <Text ta="center" c="dimmed" py="xl">
          {_('Nobody matches.')}
        </Text>
      ) : null}
      <Pagination page={people} />
    </>
  );
}

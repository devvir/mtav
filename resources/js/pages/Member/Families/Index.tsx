import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { FamilyMark } from '@/design';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Family, Paginated } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';
import { Badge, Card, Group, SimpleGrid, Text } from '@mantine/core';
import { IconHome } from '@tabler/icons-react';

export default function FamiliesIndex({
  families,
  filters,
}: {
  families: Paginated<Family>;
  filters: { q: string | null };
}) {
  const own = usePage().props.project?.family?.id;
  useProjectChanges({ families: ['families'], people: ['families'], lottery_runs: ['families'] });

  return (
    <>
      <PageHeader
        title={_('Families')}
        description={_('Each family ranks the units of its type and receives one in the lottery.')}
        help={
          <InfoTip title={_('What is a family?')}>
            {_(
              'The family takes part in the lottery as one: its members share one ranking, and it receives one unit.',
            )}
          </InfoTip>
        }
      />
      <div style={{ marginBottom: 16 }}>
        <QuerySearch value={filters.q} placeholder={_('Search families or people')} maw={440} />
      </div>
      <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }}>
        {families.data.map((family) => (
          <Card
            key={family.id}
            component={Link}
            href={route('families.show', family.id)}
            p="md"
            className="ds-interactive"
            data-own={family.id === own || undefined}
          >
            <Group wrap="nowrap" align="flex-start">
              <FamilyMark name={family.name} size={44} />
              <div style={{ minWidth: 0, flex: 1 }}>
                <Group gap="xs">
                  <Text fw={700}>{family.name}</Text>
                  {family.id === own ? <Badge color="green">{_('your family')}</Badge> : null}
                </Group>
                <Text className="ds-caption">
                  {family.unit_type?.name} ·{' '}
                  {_n('{count} person', '{count} people', family.members_count ?? 0)}
                </Text>
                {family.members?.length ? (
                  <Text size="sm" mt={6} lineClamp={2}>
                    {family.members.map((member) => member.firstname).join(', ')}
                  </Text>
                ) : null}
                {family.unit ? (
                  <Group gap={4} mt={6} c="var(--mtav-positive)" fw={650} fz="sm">
                    <IconHome size={16} /> {family.unit.identifier}
                  </Group>
                ) : null}
              </div>
            </Group>
          </Card>
        ))}
      </SimpleGrid>
      {!families.data.length ? (
        <Text ta="center" c="dimmed" py="xl">
          {_('No families yet.')}
        </Text>
      ) : null}
      <Pagination page={families} />
    </>
  );
}

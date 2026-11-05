import { PersonCard } from '@/components/domain/PeopleCards';
import { FoundPeople, PeopleViews, type Found } from '@/components/domain/PeopleViews';
import { PersonDetails } from '@/components/domain/PersonDetails';
import { Pagination } from '@/components/feedback/Pagination';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { useDetailsInAddress } from '@/lib/detailsInAddress';
import { setFilter } from '@/lib/listFilters';
import { activeFilters, useLocalFilters } from '@/lib/localList';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated, Person, Ref } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';
import { Button, Group, Modal, NativeSelect, SimpleGrid, Text } from '@mantine/core';
import { IconMail } from '@tabler/icons-react';

export default function PeopleIndex({
  people,
  whole,
  filters,
  families,
  found,
  person: shown,
}: {
  people: Paginated<Person>;
  /** All of them came (a short list): searched and filtered here, with no request */
  whole: boolean;
  filters: { q: string | null; family: number | null; admins: boolean };
  families: Ref[];
  /** What a search found, families and admins too */
  found: Found | null;
  /** Someone's details, shown over the list (?person=19) */
  person: { person: Person; families: Ref[] | null } | null;
}) {
  const details = useDetailsInAddress('person');
  useProjectChanges({ people: ['people', 'person'] });
  const project = usePage().props.project!;
  const canManage = project.can.manage;
  const [local, setLocal] = useLocalFilters(filters);
  const narrow = <K extends keyof typeof filters>(key: K, value: (typeof filters)[K]) =>
    whole ? setLocal(key, value) : setFilter(key, value);
  const current = whole ? local : filters;
  // Admins are a view of their own: their tab, not a filter here
  const listed = whole
    ? people.data.filter(
        (person) =>
          (!local.family || person.family?.id === local.family) &&
          (!filters.admins || person.is_admin),
      )
    : people.data;

  return (
    <>
      <PageHeader
        title={_('People')}
        tour="member.people"
        help={
          <InfoTip title={_('Everyone in the project')} tour="member.people">
            {_('Every person in the project, the admins too. Open someone to see what they share.')}
          </InfoTip>
        }
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
      <PeopleViews q={filters.q} />
      {found && filters.q ? (
        <FoundPeople found={found} q={filters.q} own={project.family?.id} />
      ) : (
        <>
          <Group mb="md" gap="sm" align="center">
            <NativeSelect
              aria-label={_('Family')}
              value={current.family ? String(current.family) : ''}
              onChange={(event) => narrow('family', Number(event.currentTarget.value) || null)}
              data={[
                { value: '', label: _('All families') },
                ...families.map((family) => ({ value: String(family.id), label: family.name })),
              ]}
              w={220}
            />
          </Group>
          <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} data-tour="people.list">
            {listed.map((person) => (
              <PersonCard
                key={person.id}
                person={person}
                href={route('people.index', { ...activeFilters(current), person: person.id })}
              />
            ))}
          </SimpleGrid>
          {!listed.length ? (
            <Text ta="center" c="dimmed" py="xl">
              {_('Nobody matches.')}
            </Text>
          ) : null}
          <Pagination page={people} />
        </>
      )}

      <Modal opened={Boolean(shown)} onClose={details.close} title={shown?.person.name} size="lg">
        {shown ? <PersonDetails person={shown.person} families={shown.families} /> : null}
      </Modal>
    </>
  );
}

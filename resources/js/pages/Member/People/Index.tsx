import { PeopleViews } from '@/components/domain/PeopleViews';
import { PersonDetails } from '@/components/domain/PersonDetails';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar } from '@/design';
import { useDetailsInAddress } from '@/lib/detailsInAddress';
import { setFilter } from '@/lib/listFilters';
import { activeFilters, matches, useLocalFilters } from '@/lib/localList';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated, Person, Ref } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';
import {
  Badge,
  Button,
  Card,
  Group,
  Modal,
  NativeSelect,
  SimpleGrid,
  Switch,
  Text,
} from '@mantine/core';
import { IconMail } from '@tabler/icons-react';

export default function PeopleIndex({
  people,
  whole,
  filters,
  families,
  person: shown,
}: {
  people: Paginated<Person>;
  /** All of them came (a short list): searched and filtered here, with no request */
  whole: boolean;
  filters: { q: string | null; family: number | null; admins: boolean };
  families: Ref[];
  /** Someone's details, shown over the list (?person=19) */
  person: { person: Person; families: Ref[] | null } | null;
}) {
  const details = useDetailsInAddress('person');
  useProjectChanges({ people: ['people', 'person'] });
  const canManage = usePage().props.project!.can.manage;
  const [local, setLocal] = useLocalFilters(filters);
  const narrow = <K extends keyof typeof filters>(key: K, value: (typeof filters)[K]) =>
    whole ? setLocal(key, value) : setFilter(key, value);
  const current = whole ? local : filters;
  // Email only where it is shown to this person (shared, or they are an admin)
  const listed = whole
    ? people.data.filter(
        (person) =>
          matches(local.q, person.name, person.family?.name, person.email) &&
          (!local.family || person.family?.id === local.family) &&
          (!local.admins || person.is_admin),
      )
    : people.data;

  return (
    <>
      <PageHeader
        title={_('People')}
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
      <PeopleViews />
      <Group mb="md" gap="sm" align="center">
        <QuerySearch
          value={filters.q}
          onSearch={whole ? (query) => setLocal('q', query || null) : undefined}
          placeholder={canManage ? _('Search by name or email') : _('Search by name')}
          maw={360}
        />
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
        <Switch
          name="admins"
          label={_('Only admins')}
          checked={current.admins}
          onChange={(event) => narrow('admins', event.currentTarget.checked)}
        />
      </Group>
      <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }}>
        {listed.map((person) => (
          <Card
            key={person.id}
            component={Link}
            href={route('people.index', { ...activeFilters(current), person: person.id })}
            only={['person']}
            preserveState
            preserveScroll
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
      {!listed.length ? (
        <Text ta="center" c="dimmed" py="xl">
          {_('Nobody matches.')}
        </Text>
      ) : null}
      <Pagination page={people} />

      <Modal opened={Boolean(shown)} onClose={details.close} title={shown?.person.name} size="lg">
        {shown ? <PersonDetails person={shown.person} families={shown.families} /> : null}
      </Modal>
    </>
  );
}

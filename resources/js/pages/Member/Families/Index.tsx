import { ArchivedList, ArchiveSwitch, type ArchiveState } from '@/components/domain/Archive';
import { FamilyDetails, type FamilyRequest } from '@/components/domain/FamilyDetails';
import { PeopleViews } from '@/components/domain/PeopleViews';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { FamilyForm } from '@/components/forms/FamilyForm';
import { FamilyInvitationForm } from '@/components/forms/FamilyInvitationForm';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, FamilyMark } from '@/design';
import { useAddressedDialog } from '@/lib/addressedDialog';
import { useDetailsInAddress } from '@/lib/detailsInAddress';
import { setFilter } from '@/lib/listFilters';
import { activeFilters, matches, useLocalFilters } from '@/lib/localList';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Family, Paginated, Ref, UnitType } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';
import { Badge, Button, Card, Group, Modal, NativeSelect, SimpleGrid, Text } from '@mantine/core';
import { IconHome, IconPlus, IconUsersGroup } from '@tabler/icons-react';
import { useState } from 'react';

export default function FamiliesIndex({
  families,
  whole,
  filters,
  types,
  unitTypes,
  family: shown,
  requests,
  archive,
  guests,
}: {
  families: Paginated<Family>;
  /** All of them came (a short list): searched and filtered here, with no request */
  whole: boolean;
  filters: { q: string | null; unit_type: number | null };
  /** The project's unit types, to filter by */
  types: Ref[];
  /** For admins, who register and edit families here */
  unitTypes: UnitType[] | null;
  /** A family's details, shown over the list (?family=12) */
  family: Family | null;
  /** For those in that family: who asks to join */
  requests: FamilyRequest[] | null;
  /** For admins: the families archived, each to bring back by inviting someone */
  archive: ArchiveState | null;
  guests: { name: string; email: string }[];
}) {
  const [reviving, setReviving] = useState<Ref | null>(null);
  const details = useDetailsInAddress('family');
  const project = usePage().props.project!;
  const own = project.family?.id;
  const canManage = project.can.manage;
  const nothingYet = !families.data.length && !filters.q && !filters.unit_type;
  const [local, setLocal] = useLocalFilters(filters);
  const current = whole ? local : filters;
  // A family is found by its name, its type, and its members' names (and emails, where shown)
  const listed = whole
    ? families.data.filter(
        (family) =>
          matches(
            local.q,
            family.name,
            family.unit_type?.name,
            ...(family.members ?? []).flatMap((member) => [member.name, member.email]),
          ) &&
          (!local.unit_type || family.unit_type?.id === local.unit_type),
      )
    : families.data;
  const adding = useAddressedDialog('new', canManage);
  useProjectChanges({
    families: ['families', 'family'],
    people: ['families', 'family'],
    lottery_runs: ['families', 'family'],
  });

  return (
    <>
      <PageHeader
        title={_('People')}
        tour="member.families"
        actions={
          canManage && !nothingYet ? (
            <Button leftSection={<IconPlus size={18} />} onClick={adding.open}>
              {_('Add a family')}
            </Button>
          ) : null
        }
        help={
          <InfoTip title={_('What is a family?')} tour="member.families">
            {_(
              'The family takes part in the lottery as one: its members share one ranking, and it receives one unit.',
            )}
          </InfoTip>
        }
      />
      <PeopleViews />
      {archive ? (
        <Group mb="md">
          <ArchiveSwitch archive={archive} />
        </Group>
      ) : null}
      {archive?.shown ? (
        <ArchivedList
          archive={archive}
          kind="families"
          action={(item) => (
            <Button
              variant="default"
              onClick={() => setReviving({ id: item.id, name: item.title })}
            >
              {_('Invite someone into it')}
            </Button>
          )}
        />
      ) : nothingYet ? (
        <EmptyState
          icon={IconUsersGroup}
          title={_('No families yet')}
          action={
            canManage ? <Button onClick={adding.open}>{_('Add the first family')}</Button> : null
          }
        >
          {canManage
            ? _('Add each family with its unit type, and invite someone in it.')
            : _('Families show up here as people join them.')}
        </EmptyState>
      ) : (
        <Group mb="md" gap="sm">
          <QuerySearch
            value={filters.q}
            placeholder={_('Search families or people')}
            maw={360}
            onSearch={whole ? (query) => setLocal('q', query || null) : undefined}
          />
          {types.length > 1 ? (
            <NativeSelect
              aria-label={_('Unit type')}
              value={current.unit_type ? String(current.unit_type) : ''}
              onChange={(event) => {
                const value = Number(event.currentTarget.value) || null;
                if (whole) setLocal('unit_type', value);
                else setFilter('unit_type', value);
              }}
              data={[
                { value: '', label: _('All types') },
                ...types.map((type) => ({ value: String(type.id), label: type.name })),
              ]}
              w={220}
            />
          ) : null}
        </Group>
      )}
      <SimpleGrid
        cols={{ base: 1, sm: 2, lg: 3 }}
        style={archive?.shown ? { display: 'none' } : undefined}
      >
        {listed.map((family) => (
          <Card
            key={family.id}
            component={Link}
            href={route('families.index', { ...activeFilters(current), family: family.id })}
            only={['family']}
            preserveState
            preserveScroll
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
      {!listed.length && !nothingYet && !archive?.shown ? (
        <Text ta="center" c="dimmed" py="xl">
          {current.q
            ? _('No family or person matches “{q}”.', { q: current.q })
            : _('No family of this type yet.')}
        </Text>
      ) : null}
      {archive?.shown ? null : <Pagination page={families} />}

      {unitTypes ? (
        <Modal opened={adding.opened} onClose={adding.close} title={_('Add a family')}>
          <FamilyForm unitTypes={unitTypes} onDone={adding.close} />
        </Modal>
      ) : null}

      <Modal
        opened={Boolean(reviving)}
        onClose={() => setReviving(null)}
        title={_('Bring back the {name} family', { name: reviving?.name ?? '' })}
      >
        {reviving ? (
          <FamilyInvitationForm
            family={reviving}
            guests={guests}
            onDone={() => setReviving(null)}
          />
        ) : null}
      </Modal>

      <Modal opened={Boolean(shown)} onClose={details.close} title={shown?.name} size="lg">
        {shown ? <FamilyDetails family={shown} unitTypes={unitTypes} requests={requests} /> : null}
      </Modal>
    </>
  );
}

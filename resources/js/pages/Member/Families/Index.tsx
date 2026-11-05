import {
  ArchivedList,
  ArchiveSwitch,
  type ArchivedItem,
  type ArchiveState,
} from '@/components/domain/Archive';
import { ArchivedFamilyHistory } from '@/components/domain/ArchivedFamilyHistory';
import { FamilyDetails, type FamilyRequest } from '@/components/domain/FamilyDetails';
import { FamilyCard } from '@/components/domain/PeopleCards';
import { FoundPeople, PeopleViews, type Found } from '@/components/domain/PeopleViews';
import { Pagination } from '@/components/feedback/Pagination';
import { FamilyForm } from '@/components/forms/FamilyForm';
import { FamilyInvitationForm } from '@/components/forms/FamilyInvitationForm';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { useAddressedDialog } from '@/lib/addressedDialog';
import { useDetailsInAddress } from '@/lib/detailsInAddress';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Family, Paginated, Ref, UnitType } from '@/types/models';
import { usePage } from '@inertiajs/react';
import { Button, Group, Modal, SimpleGrid, Text } from '@mantine/core';
import { IconPlus, IconUsersGroup } from '@tabler/icons-react';
import { useState } from 'react';

export default function FamiliesIndex({
  families,
  filters,
  unitTypes,
  family: shown,
  found,
  requests,
  archive,
  guests,
}: {
  families: Paginated<Family>;
  filters: { q: string | null };
  /** For admins, who register and edit families here */
  unitTypes: UnitType[] | null;
  /** A family's details, shown over the list (?family=12) */
  family: Family | null;
  /** What a search found, people and admins too */
  found: Found | null;
  /** For those in that family: who asks to join */
  requests: FamilyRequest[] | null;
  /** For admins: the families archived, each to bring back by inviting someone */
  archive: ArchiveState | null;
  guests: { name: string; email: string }[];
}) {
  const [reviving, setReviving] = useState<Ref | null>(null);
  const [looking, setLooking] = useState<ArchivedItem | null>(null);
  const details = useDetailsInAddress('family');
  const project = usePage().props.project!;
  const own = project.family?.id;
  const canManage = project.can.manage;
  const nothingYet = !families.data.length && !filters.q;
  const listed = families.data;
  const searching = Boolean(found && filters.q);
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
      <PeopleViews q={filters.q} />
      {archive && !searching ? (
        <Group mb="md">
          <ArchiveSwitch archive={archive} />
        </Group>
      ) : null}
      {searching ? (
        <FoundPeople found={found!} q={filters.q!} own={own} />
      ) : archive?.shown ? (
        <ArchivedList
          archive={archive}
          kind="families"
          action={(item) => (
            <Group gap="xs">
              <Button variant="default" onClick={() => setLooking(item)}>
                {_('History')}
              </Button>
              <Button
                variant="default"
                onClick={() => setReviving({ id: item.id, name: item.title })}
              >
                {_('Invite someone into it')}
              </Button>
            </Group>
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
      ) : null}
      {searching ? null : (
        <>
          <SimpleGrid
            cols={{ base: 1, sm: 2, lg: 3 }}
            style={archive?.shown ? { display: 'none' } : undefined}
            data-tour="families.list"
          >
            {listed.map((family) => (
              <FamilyCard
                key={family.id}
                family={family}
                own={family.id === own}
                href={route('families.index', { family: family.id })}
              />
            ))}
          </SimpleGrid>
          {!listed.length && !nothingYet && !archive?.shown ? (
            <Text ta="center" c="dimmed" py="xl">
              {_('No family of this type yet.')}
            </Text>
          ) : null}
          {archive?.shown ? null : <Pagination page={families} />}
        </>
      )}

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

      <ArchivedFamilyHistory family={looking} onClose={() => setLooking(null)} />

      <Modal opened={Boolean(shown)} onClose={details.close} title={shown?.name} size="lg">
        {shown ? <FamilyDetails family={shown} unitTypes={unitTypes} requests={requests} /> : null}
      </Modal>
    </>
  );
}

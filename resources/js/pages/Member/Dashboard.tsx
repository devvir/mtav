import { CommunityPanel, type Community } from '@/components/domain/CommunityPanel';
import { EventCard } from '@/components/domain/EventCard';
import { MediaGrid } from '@/components/domain/MediaGrid';
import { SetupChecklist, isSetUp, type Setup } from '@/components/domain/SetupChecklist';
import { UnitsByType } from '@/components/domain/UnitsByType';
import { DateTime } from '@/components/feedback/DateTime';
import { FamilyForm } from '@/components/forms/FamilyForm';
import { InfoTip } from '@/components/help/InfoTip';
import { FoldablePanel } from '@/components/layout/FoldablePanel';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, Glyph, Stat } from '@/design';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media, ProjectEvent, Ref, UnitType } from '@/types/models';
import { Deferred, Link, usePage } from '@inertiajs/react';
import {
  Anchor,
  Button,
  Card,
  Group,
  Modal,
  SimpleGrid,
  Skeleton,
  Stack,
  Text,
  Title,
} from '@mantine/core';
import {
  IconCalendarEvent,
  IconChevronRight,
  IconDice5,
  IconHomeHeart,
  IconLayoutGrid,
  IconPhoto,
  IconUsers,
  IconUsersGroup,
} from '@tabler/icons-react';
import { useState } from 'react';

interface Props {
  overview: {
    description: string;
    organization: string | null;
    families_count: number;
    members_count: number;
    units_count: number;
  };
  lottery: {
    start_date: string | null;
    status: string | null;
    unit: { id: number; identifier: string } | null;
  };
  /** For someone with no family yet, who can start theirs: the types it can have */
  ownFamilyTypes: Ref[] | null;
  /** For admins: what is left to set up (it follows the page) */
  setup?: Setup | null;
  events?: ProjectEvent[];
  units?: UnitType[];
  media?: Media[];
  community?: Community;
}

/**
 * Cooperatives' own pictures (needing no credit), softened, behind a project with nothing
 * shared yet: what the panel will look like, rather than an empty box
 */
const SHARED_EMPTY_PHOTOS = ['vicman', 'mutual-aid', 'work-day', 'fray-bentos'];

/** Someone who joined with no family: they start theirs, or someone of it adds them */
function NoFamilyYet({ types }: { types: Ref[] }) {
  const [creating, setCreating] = useState(false);

  return (
    <Card p="lg" mb="lg" data-tour="home.no_family">
      <Group gap="sm">
        <Glyph icon={IconUsersGroup} tone="brand" />
        <Title order={2} fz="lg">
          {_('You have no family yet')}
        </Title>
      </Group>
      <Text c="dimmed" mt="sm">
        {_(
          'To take part in the lottery, create your family. If it is already here, ask someone in it to add you with your email.',
        )}
      </Text>
      {types.length ? (
        <Button mt="md" style={{ alignSelf: 'flex-start' }} onClick={() => setCreating(true)}>
          {_('Create our family')}
        </Button>
      ) : (
        <Text className="ds-caption" mt="md">
          {_('You can create it once the admins add the unit types.')}
        </Text>
      )}
      <Modal opened={creating} onClose={() => setCreating(false)} title={_('Create our family')}>
        <FamilyForm own unitTypes={types} onDone={() => setCreating(false)} />
      </Modal>
    </Card>
  );
}

/**
 * The project's home, in the order people look for things: its figures, what was shared,
 * the lottery and what is coming up, its people, its units, and for admins the path to the
 * lottery (first while the project is still empty, when it is all there is to do). The
 * page paints at once; each part shows a skeleton until its data arrives.
 */
export default function Dashboard({
  overview,
  lottery,
  ownFamilyTypes,
  setup,
  events,
  media,
  community,
  units,
}: Props) {
  const project = usePage().props.project!;
  // Nothing registered yet (no units, no families): no figures worth showing, only what comes next
  const empty = !overview.units_count && !overview.families_count;
  useProjectChanges({
    events: ['events'],
    media: ['media'],
    lottery_runs: ['lottery'],
    families: ['overview', 'setup', 'community', 'units'],
    people: ['overview', 'setup', 'community'],
    units: ['overview', 'setup', 'units'],
    unit_types: ['setup', 'units'],
  });

  // Admins only: what is left to set up, until it is all done
  const checklist = project.can.manage ? (
    <Deferred data="setup" fallback={<Skeleton h={300} radius="lg" mt={24} />}>
      {setup && !isSetUp(setup) ? (
        <div style={{ marginTop: 24 }}>
          <SetupChecklist setup={setup} />
        </div>
      ) : (
        <></>
      )}
    </Deferred>
  ) : null;

  return (
    <>
      <PageHeader
        title={project.name}
        description={overview.description}
        tour="member.home"
        help={
          <InfoTip title={_('The project at a glance')} tour="member.home">
            {_(
              'Its figures, what was shared lately, the lottery and what is coming up, its people and its units.',
            )}
          </InfoTip>
        }
      />

      {ownFamilyTypes ? <NoFamilyYet types={ownFamilyTypes} /> : null}

      {empty ? checklist : null}

      {!empty ? (
        <SimpleGrid cols={{ base: 1, xs: 3 }} mb="lg" data-tour="home.figures">
          <Link href={route('families.index')} className="stat-link">
            <Stat
              label={_('Families')}
              value={overview.families_count}
              icon={IconUsersGroup}
              tone="brand"
            />
          </Link>
          <Link href={route('people.index')} className="stat-link">
            <Stat label={_('People')} value={overview.members_count} icon={IconUsers} tone="info" />
          </Link>
          <Link href={route('units.index')} className="stat-link">
            <Stat
              label={_('Units')}
              value={overview.units_count}
              icon={IconLayoutGrid}
              tone="accent"
            />
          </Link>
        </SimpleGrid>
      ) : null}
      {/* Members, until there are units to choose from: what is under way */}
      {!overview.units_count && !project.can.manage ? (
        <div style={{ marginBottom: 24 }}>
          <EmptyState icon={IconHomeHeart} title={_('Your project is getting ready')}>
            {_(
              'The admins are adding the units and registering the families: everything shows up here as they do.',
            )}
          </EmptyState>
        </div>
      ) : null}

      {/* The lottery, the heart of the project: here once it has a date, up front */}
      {lottery.status === 'completed' || lottery.status === 'running' || lottery.start_date ? (
        <Card
          component={Link}
          href={route('lottery.show')}
          p="lg"
          mb="lg"
          className="ds-interactive lottery-banner"
          data-tour="home.lottery"
          data-done={lottery.status === 'completed' || undefined}
        >
          <Glyph
            icon={IconDice5}
            size={48}
            tone={lottery.status === 'completed' ? 'positive' : 'brand'}
          />
          <div style={{ flex: 1, minWidth: 0 }}>
            <Title order={2} fz="lg">
              {_('Lottery')}
            </Title>
            {lottery.status === 'completed' ? (
              lottery.unit ? (
                <Text mt={4}>
                  {_('Your family received')}{' '}
                  <strong className="text-positive">{lottery.unit.identifier}</strong>.
                </Text>
              ) : (
                <Text c="dimmed" mt={4}>
                  {_('The lottery is done. See the results.')}
                </Text>
              )
            ) : lottery.status === 'running' ? (
              <Text c="dimmed" mt={4}>
                {_('The lottery is running right now.')}
              </Text>
            ) : (
              <Text c="dimmed" mt={4}>
                {_('Scheduled for')}{' '}
                <Text component="strong" c="var(--mtav-text)" fw={650}>
                  <DateTime value={lottery.start_date!} />
                </Text>
                .{' '}
                {project.can.rank ? _("Make sure your family's ranking is how you want it.") : null}
              </Text>
            )}
          </div>
          <IconChevronRight size={22} className="lottery-banner-go" aria-hidden />
        </Card>
      ) : null}

      <div className="dashboard-pair">
        {project.features.gallery ? (
          <FoldablePanel
            name="home.shared"
            title={_('Recently shared')}
            icon={IconPhoto}
            tone="accent"
            actions={
              <Anchor component={Link} href={route('media.index')} size="sm" fw={600}>
                {_('All files')}
              </Anchor>
            }
          >
            <Deferred data="media" fallback={<Skeleton h={160} radius="lg" />}>
              {media?.length ? (
                <MediaGrid media={media} />
              ) : (
                <div className="shared-empty">
                  <div className="shared-empty-photos" aria-hidden>
                    {SHARED_EMPTY_PHOTOS.map((photo) => (
                      <img
                        key={photo}
                        src={`/images/welcome/${photo}.webp`}
                        alt=""
                        loading="lazy"
                      />
                    ))}
                  </div>
                  <div className="shared-empty-note">
                    <p className="ds-empty-title">{_('Nothing shared yet')}</p>
                    <p className="ds-caption ds-empty-text">
                      {_('Photos of the works, plans and documents, for everyone in the project.')}
                    </p>
                    {project.can.upload ? (
                      <Button component={Link} href={route('media.index', { share: 1 })}>
                        {_('Share something')}
                      </Button>
                    ) : null}
                  </div>
                </div>
              )}
            </Deferred>
          </FoldablePanel>
        ) : null}

        {project.features.events ? (
          <FoldablePanel
            name="home.events"
            title={_('Coming up')}
            icon={IconCalendarEvent}
            actions={
              <Anchor component={Link} href={route('events.index')} size="sm" fw={600}>
                {_('All events')}
              </Anchor>
            }
          >
            <Deferred data="events" fallback={<Skeleton h={96} radius="lg" />}>
              {events?.length ? (
                <Stack gap="sm">
                  {events.map((event) => (
                    <EventCard key={event.id} event={event} />
                  ))}
                </Stack>
              ) : (
                <EmptyState
                  inset
                  icon={IconCalendarEvent}
                  title={_('No events coming up')}
                  action={
                    project.can.manage ? (
                      <Button
                        component={Link}
                        href={route('events.index', { new: 1 })}
                        variant="light"
                      >
                        {_('Create an event')}
                      </Button>
                    ) : null
                  }
                >
                  {_('Assemblies, work days and meetings show up here.')}
                </EmptyState>
              )}
            </Deferred>
          </FoldablePanel>
        ) : null}
      </div>

      {!empty ? (
        <div style={{ marginTop: 24 }}>
          <Deferred data="community" fallback={<Skeleton h={220} radius="lg" />}>
            {community ? <CommunityPanel community={community} /> : null}
          </Deferred>
        </div>
      ) : null}

      {!empty ? (
        <div style={{ marginTop: 24 }}>
          <Deferred data="units" fallback={<Skeleton h={160} radius="lg" />}>
            {units ? <UnitsByType unitTypes={units} away /> : <></>}
          </Deferred>
        </div>
      ) : null}

      {!empty ? checklist : null}
    </>
  );
}

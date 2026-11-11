import { LotteryAdminTools, type LotteryAdmin } from '@/components/domain/LotteryAdminTools';
import { LotteryPlan } from '@/components/domain/LotteryPlan';
import { LotteryProgress } from '@/components/domain/LotteryProgress';
import { usePlanFullScreen } from '@/components/domain/PlanFullScreen';
import { RankingList } from '@/components/domain/RankingList';
import { ChecklistCorner, isSetUp, type Setup } from '@/components/domain/SetupChecklist';
import { DateTime } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, Glyph, Panel, Status } from '@/design';
import { heldOffline } from '@/lib/offline';
import { useRankingSave, type RankingSave } from '@/lib/rankingSave';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _nodes } from '@/lib/translations';
import type { LotteryRun, Plan as PlanData, ProjectEvent, Unit } from '@/types/models';
import { Deferred, Link } from '@inertiajs/react';
import {
  Alert,
  Anchor,
  Badge,
  Card,
  Group,
  Skeleton,
  Stack,
  Text,
  Title,
  Transition,
} from '@mantine/core';
import {
  IconAlertTriangle,
  IconClock,
  IconHome,
  IconListNumbers,
  IconMap,
  IconTrophy,
} from '@tabler/icons-react';
import { useState } from 'react';

interface Props {
  lottery: ProjectEvent;
  status: 'running' | 'completed' | null;
  /** Whether the server can run it now (nothing works its queue otherwise) */
  runnable: boolean;
  /** A run under way: when it began and when it ends */
  pace: { started_at: string; reveal_at: string | null } | null;
  runs: LotteryRun[];
  family: { id: number; name: string; unit_type: string; unit_type_id: number } | null;
  plan?: PlanData;
  ranking: Unit[] | null;
  assignment:
    { id: number; name: string; unit: { id: number; identifier: string } | null }[] | null;
  canRank: boolean;
  /** For admins: what running the lottery takes */
  admin: LotteryAdmin | null;
  /** What is left on the way to it (it follows the page) */
  setup?: Setup;
}

/**
 * The lottery as people live it: when it happens, the family's ranking (saved as it
 * changes), the outcome, and every invalidation with its reason; admins also run it here.
 */
export default function Lottery({
  lottery,
  status,
  runnable,
  pace,
  runs,
  family,
  ranking: given,
  assignment,
  canRank,
  plan,
  setup,
  admin,
}: Props) {
  const {
    ranking,
    rank: move,
    state: saveState,
  } = useRankingSave(given, route('lottery.preferences'));
  const planFull = usePlanFullScreen();
  // The unit being moved or pointed at (lit up on the plan); one picked on the plan (lit up
  // a moment in the ranking)
  const [focused, setFocused] = useState<number | null>(null);
  const [flash, setFlash] = useState<{ unit: number; at: number } | null>(null);

  useProjectChanges({
    lottery_runs: ['status', 'pace', 'runs', 'assignment', 'canRank'],
    units: ['ranking'],
    unit_types: ['ranking'],
    families: ['family', 'ranking'],
  });

  /** A new order, shown and saved; offline, neither (nothing shown that is not saved) */
  const rank = (units: Unit[]) => {
    if (heldOffline()) return;
    move(units);
  };

  const ownUnit = assignment?.find((row) => row.id === family?.id)?.unit ?? null;
  const invalidations = runs.filter((run) => run.status === 'invalidated');
  const completedRun = runs.find((run) => run.status === 'completed');

  return (
    <>
      <PageHeader
        tour="lottery"
        title={_('Lottery')}
        actions={
          <Deferred data="setup" fallback={<></>}>
            {setup && !isSetUp(setup) ? <ChecklistCorner setup={setup} /> : <></>}
          </Deferred>
        }
        description={lottery.description}
        help={
          <InfoTip title={_('How the lottery works')} tour="lottery">
            <p>
              {_(
                'Each family ranks the units of its type. The lottery first gives the family that would be worst off the best it can, then makes the whole project as happy as possible. Only ties are settled at random.',
              )}
            </p>
            <p>
              {_(
                'The result is final. If a run is ever invalidated, everyone sees who did it and why.',
              )}
            </p>
          </InfoTip>
        }
      />

      <Card p="lg" mb="lg" data-tour="lottery.status" className="lottery-status">
        {status === 'completed' ? (
          <Group align="flex-start" wrap="nowrap">
            <Glyph icon={IconTrophy} tone="positive" size={48} />
            <div>
              <Title order={2} fz="lg">
                {_('The lottery is done')}
              </Title>
              {ownUnit ? (
                <Text mt={4}>
                  {_nodes('Your family received {unit}.', {
                    unit: <strong className="text-positive">{ownUnit.identifier}</strong>,
                  })}
                </Text>
              ) : null}
              {completedRun ? (
                <Anchor
                  component={Link}
                  href={route('lottery.record', { lotteryRun: completedRun.id })}
                  size="sm"
                  fw={650}
                  mt="xs"
                  display="inline-block"
                >
                  {_('Check the result')}
                </Anchor>
              ) : null}
            </div>
          </Group>
        ) : status === 'running' && pace ? (
          <LotteryProgress startedAt={pace.started_at} revealAt={pace.reveal_at} />
        ) : (
          <Group align="flex-start" wrap="nowrap">
            <Glyph icon={IconClock} size={48} />
            <div>
              <Title order={2} fz="lg">
                {lottery.start_date
                  ? _nodes('Scheduled for {date}', {
                      date: <DateTime value={lottery.start_date} />,
                    })
                  : _('Not scheduled yet')}
              </Title>
              <Text c="dimmed" mt={4}>
                {_('Until then, every family can change its ranking as often as it likes.')}
              </Text>
            </div>
          </Group>
        )}
      </Card>

      {admin?.families_without_people.length && status !== 'completed' ? (
        <Alert color="yellow" radius="lg" mb="lg" icon={<IconAlertTriangle size={20} />}>
          {_('Nobody has joined these families yet, only invited: {families}.', {
            families: admin.families_without_people.join(', '),
          })}
        </Alert>
      ) : null}

      {/* Said before anyone tries: the lottery is held until the server can run it */}
      {!runnable && status !== 'completed' ? (
        <Alert color="yellow" radius="lg" mb="lg" icon={<IconAlertTriangle size={20} />}>
          {_("The lottery can't run right now. We're looking into it. Try again later.")}
        </Alert>
      ) : null}

      {admin ? (
        <LotteryAdminTools lottery={lottery} status={status} runnable={runnable} {...admin} />
      ) : null}

      {/* Invalidations are always visible: everyone must know a draw was undone, and why */}
      {invalidations.length ? (
        <Stack gap="sm" mb="lg">
          {invalidations.map((run) => (
            <Alert key={run.id} color="yellow" radius="lg" icon={<IconAlertTriangle size={20} />}>
              <Text fw={700}>
                {_nodes(
                  run.invalidated_by
                    ? '{name} invalidated a lottery run · {date}'
                    : 'A lottery run was invalidated · {date}',
                  {
                    name: run.invalidated_by,
                    date: <DateTime value={run.invalidated_at} />,
                  },
                )}
              </Text>
              <Text mt={4}>«{run.invalidation_reason}»</Text>
            </Alert>
          ))}
        </Stack>
      ) : null}

      {/* The plan across the page: there is too much in it for a column */}
      {family ? (
        <Panel
          data-tour="lottery.plan"
          title={_('Project plan')}
          icon={IconMap}
          className="plan-panel"
          {...planFull.panel}
          actions={planFull.button}
          tone="accent"
          aside={_('{type} units are highlighted, with their place in your ranking.', {
            type: family.unit_type,
          })}
        >
          <Deferred data="plan" fallback={<Skeleton h={260} radius="lg" />}>
            {plan ? (
              <LotteryPlan
                plan={plan}
                ranking={ranking}
                highlightType={family.unit_type_id}
                ownUnit={ownUnit?.id ?? null}
                canRank={canRank}
                focused={focused}
                onFocus={setFocused}
                onPick={(unit) => setFlash({ unit, at: Date.now() })}
                onChange={rank}
              />
            ) : null}
          </Deferred>
        </Panel>
      ) : null}

      <div className="two-columns" style={{ marginTop: 24 }}>
        {family ? (
          <Panel
            data-tour="ranking.list"
            title={_('Your preferences')}
            icon={IconListNumbers}
            actions={<SaveNote state={saveState} />}
          >
            {ranking.length ? (
              <>
                <Text data-tour="ranking.saving" className="ds-caption" mb="sm">
                  {canRank
                    ? _(
                        '{type} units, most wanted first. Changes are saved as you make them, for the whole family.',
                        { type: family.unit_type },
                      )
                    : _('{type} units, as your family ranked them.', { type: family.unit_type })}
                </Text>
                <RankingList
                  items={ranking}
                  label={(unit) => unit.identifier}
                  disabled={!canRank}
                  onFocus={setFocused}
                  flash={flash}
                  onChange={rank}
                />
              </>
            ) : (
              // Nothing of the family's type to rank yet: said, rather than no ranking at all
              <EmptyState
                inset
                icon={IconHome}
                title={_('No {type} units yet', { type: family.unit_type })}
              />
            )}
          </Panel>
        ) : null}

        {assignment ? (
          <Panel
            data-tour="lottery.results"
            title={_('Results')}
            icon={IconHome}
            tone="positive"
            flush
          >
            {assignment.map((row) => (
              <Group
                key={row.id}
                className="row"
                justify="space-between"
                data-own={row.id === family?.id || undefined}
              >
                <Text fw={600}>{row.name}</Text>
                <Badge color={row.unit ? 'green' : 'gray'}>
                  {row.unit?.identifier ?? _('No unit')}
                </Badge>
              </Group>
            ))}
          </Panel>
        ) : null}
      </div>
    </>
  );
}

/** How saving a ranking goes, said to screen readers too; the last word stays as it fades */
function SaveNote({ state }: { state: RankingSave }) {
  const [word, setWord] = useState(state);
  if (state && state !== word) setWord(state);

  return (
    <span role="status">
      <Transition mounted={state !== null} transition="fade" duration={200}>
        {(styles) => (
          <span style={styles}>
            {word === 'saving' ? (
              <Status tone="info" live>
                {_('Saving')}
              </Status>
            ) : (
              <Status tone="positive">{_('Saved')}</Status>
            )}
          </span>
        )}
      </Transition>
    </span>
  );
}

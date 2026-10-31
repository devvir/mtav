import { LotteryAdminTools, type LotteryAdmin } from '@/components/domain/LotteryAdminTools';
import { LotteryPlan } from '@/components/domain/LotteryPlan';
import { LotteryProgress } from '@/components/domain/LotteryProgress';
import { usePlanFullScreen } from '@/components/domain/PlanFullScreen';
import { RankingList } from '@/components/domain/RankingList';
import { ChecklistNotebook, isSetUp, type Setup } from '@/components/domain/SetupChecklist';
import { DateTime } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Glyph, Panel, Status } from '@/design';
import { heldOffline } from '@/lib/offline';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { LotteryRun, Plan as PlanData, ProjectEvent, Unit } from '@/types/models';
import { Deferred, Link, router } from '@inertiajs/react';
import { Alert, Anchor, Badge, Card, Group, Skeleton, Stack, Text, Title } from '@mantine/core';
import { useDebouncedCallback } from '@mantine/hooks';
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
  const [ranking, setRanking] = useState<Unit[]>(given ?? []);
  const [saving, setSaving] = useState(false);
  const planFull = usePlanFullScreen();
  // The unit being moved or pointed at (lit up on the plan); one picked on the plan (lit up
  // a moment in the ranking)
  const [focused, setFocused] = useState<number | null>(null);
  const [flash, setFlash] = useState<{ unit: number; at: number } | null>(null);

  // A fresh ranking from the server (someone else in the family changed it) replaces ours
  const [previous, setPrevious] = useState(given);
  if (given !== previous) {
    setPrevious(given);
    setRanking(given ?? []);
  }

  useProjectChanges({
    lottery_runs: ['status', 'pace', 'runs', 'assignment', 'canRank'],
    units: ['ranking'],
    unit_types: ['ranking'],
    families: ['family', 'ranking'],
  });

  const save = useDebouncedCallback((units: Unit[]) => {
    setSaving(true);
    router.put(
      route('lottery.preferences'),
      { preferences: units.map((unit) => unit.id) },
      {
        preserveScroll: true,
        preserveState: true,
        only: ['ranking', 'flash'],
        onFinish: () => setSaving(false),
      },
    );
  }, 600);

  /** A new order, shown and saved; offline, neither (nothing shown that is not saved) */
  const rank = (units: Unit[]) => {
    if (heldOffline()) return;
    setRanking(units);
    save(units);
  };

  const ownUnit = assignment?.find((row) => row.id === family?.id)?.unit ?? null;
  const invalidations = runs.filter((run) => run.status === 'invalidated');
  const completedRun = runs.find((run) => run.status === 'completed');

  return (
    <>
      <PageHeader
        tour="lottery"
        title={_('Lottery')}
        description={lottery.description}
        help={
          <InfoTip title={_('How the lottery works')} tour="lottery">
            <p>
              {_(
                'Each family ranks the units of its type. The lottery first makes the worst-off family as well off as possible, then the whole project as satisfied as possible; only ties are drawn at random.',
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
                  {_('Your family received')}{' '}
                  <strong className="text-positive">{ownUnit.identifier}</strong>.
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
                {lottery.start_date ? (
                  <>
                    {_('Scheduled for')} <DateTime value={lottery.start_date} />
                  </>
                ) : (
                  _('Not scheduled yet')
                )}
              </Title>
              <Text c="dimmed" mt={4}>
                {_('Until then, every family can change its ranking as often as it likes.')}
              </Text>
            </div>
          </Group>
        )}
      </Card>

      {/* Said before anyone tries: the lottery is held until the server can run it */}
      {!runnable && status !== 'completed' ? (
        <Alert color="yellow" radius="lg" mb="lg" icon={<IconAlertTriangle size={20} />}>
          {_(
            'The server cannot run the lottery right now: its background work has stopped. It is being looked into; try again later.',
          )}
        </Alert>
      ) : null}

      <Deferred data="setup" fallback={<></>}>
        {setup && !isSetUp(setup) ? <ChecklistNotebook setup={setup} /> : <></>}
      </Deferred>

      {admin ? (
        <LotteryAdminTools lottery={lottery} status={status} runnable={runnable} {...admin} />
      ) : null}

      {/* Invalidations are always visible: everyone must know a draw was undone, and why */}
      {invalidations.length ? (
        <Stack gap="sm" mb="lg">
          {invalidations.map((run) => (
            <Alert key={run.id} color="yellow" radius="lg" icon={<IconAlertTriangle size={20} />}>
              <Text fw={700}>
                {_('A lottery run was invalidated')}{' '}
                {run.invalidated_by ? _('by {name}', { name: run.invalidated_by }) : null} ·{' '}
                <DateTime value={run.invalidated_at} />
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
        {family && ranking.length ? (
          <Panel
            data-tour="ranking.list"
            title={_('Your preferences')}
            icon={IconListNumbers}
            actions={
              saving ? (
                <Status tone="info" live>
                  {_('Saving')}
                </Status>
              ) : null
            }
          >
            <Text data-tour="ranking.saving" className="ds-caption" mb="sm">
              {canRank
                ? _(
                    '{type} units, most wanted first. Changes are saved as you make them, for the whole family.',
                    { type: family.unit_type },
                  )
                : _('{type} units, as your family ranked them.', { type: family.unit_type })}
            </Text>
            <RankingList
              units={ranking}
              disabled={!canRank}
              onFocus={setFocused}
              flash={flash}
              onChange={rank}
            />
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

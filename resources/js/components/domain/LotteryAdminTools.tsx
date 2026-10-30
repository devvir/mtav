import { DateTime } from '@/components/feedback/DateTime';
import { DateTimeField } from '@/components/forms/DateTimeField';
import { Form } from '@/components/forms/Form';
import { InfoTip } from '@/components/help/InfoTip';
import { Panel, Status, type Tone } from '@/design';
import { confirm as ask, prompt } from '@/lib/dialogs';
import { dateTime, field } from '@/lib/forms';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { LotteryRun, ProjectEvent } from '@/types/models';
import { Link, router, useForm } from '@inertiajs/react';
import {
  Alert,
  Anchor,
  Box,
  Button,
  Group,
  Loader,
  Stack,
  Text,
  Textarea,
  Title,
} from '@mantine/core';
import {
  IconAlertCircle,
  IconArrowBackUp,
  IconCalendarEvent,
  IconCircleCheckFilled,
  IconDice5,
  IconHistory,
  IconListCheck,
} from '@tabler/icons-react';
import { useEffect } from 'react';

/** What admins get on the lottery page besides what everyone sees */
export interface LotteryAdmin {
  readiness: { id: number; name: string; units_count: number; families_count: number }[];
  runs: LotteryRun[];
  confirm: { option: string; message: string } | null;
}

interface Props extends LotteryAdmin {
  lottery: ProjectEvent;
  status: 'running' | 'completed' | null;
}

const local = (iso: string | null) => {
  if (!iso) return '';
  const date = new Date(iso);
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

const TONES: Record<LotteryRun['status'], Tone> = {
  running: 'info',
  completed: 'positive',
  failed: 'negative',
  invalidated: 'warning',
};

/**
 * Running the lottery, for admins on the lottery page: schedule, readiness per unit type,
 * the run, and every run's record (including invalidations, which everyone reads).
 */
export function LotteryAdminTools({
  lottery,
  status,
  runnable,
  readiness,
  runs,
  confirm,
}: Props & { runnable: boolean }) {
  const schedule = useForm({
    description: lottery.description,
    start_date: local(lottery.start_date),
  });

  useProjectChanges({
    lottery_runs: ['status', 'admin'],
    units: ['admin'],
    families: ['admin'],
    unit_types: ['admin'],
  });

  const saveSchedule = () => {
    schedule.transform((data) => ({
      ...data,
      start_date: data.start_date ? new Date(data.start_date).toISOString() : null,
    }));
    schedule.put(route('manage.lottery.schedule'), { preserveScroll: true });
  };

  const balanced = readiness.every((type) => type.units_count === type.families_count);
  const due = Boolean(lottery.start_date) && new Date(lottery.start_date!) <= new Date();

  const run = async (options: string[] = []) => {
    const ok = await ask({
      title: _('Run the lottery now?'),
      message: _(
        'Every family receives a unit from its ranking. The result is final; only an invalidation, with a reason everyone reads, can undo it.',
      ),
      typeToConfirm: _('RUN'),
      confirmLabel: _('Run the lottery'),
    });
    if (ok) router.post(route('manage.lottery.run'), { options }, { preserveScroll: true });
  };

  /** The server asks for an explicit confirmation (more units than families, say) */
  useEffect(() => {
    if (!confirm) return;
    ask({
      title: _('Before running'),
      message: confirm.message,
      confirmLabel: _('Run anyway'),
    }).then(
      (ok) =>
        ok &&
        router.post(
          route('manage.lottery.run'),
          { options: [confirm.option] },
          { preserveScroll: true },
        ),
    );
  }, [confirm]);

  const invalidate = async (item: LotteryRun) => {
    const reason = await prompt({
      title: _('Invalidate the lottery?'),
      message: _(
        'Every assignment is cleared and the lottery can run again. Everyone in the project will read who did it and why.',
      ),
      label: _('Why it is invalidated'),
      multiline: true,
      minLength: 10,
      confirmLabel: _('Invalidate'),
      danger: true,
    });
    if (reason)
      router.post(
        route('manage.lottery.invalidate', item.id),
        { reason },
        { preserveScroll: true },
      );
  };

  const labels: Record<LotteryRun['status'], string> = {
    running: _('Running'),
    completed: _('Completed'),
    failed: _('Failed'),
    invalidated: _('Invalidated'),
  };

  return (
    <section style={{ marginBottom: 32 }} aria-label={_('Running the lottery')}>
      <Group gap={6} mb="sm">
        <Title order={2} fz="lg">
          {_('Running the lottery')}
        </Title>
        <InfoTip title={_('Running the lottery')} tour="manage.lottery">
          {_(
            'Once its date arrives, you run the lottery and every family receives a unit from its ranking, fairly and in one go. The result is final, and recorded.',
          )}
        </InfoTip>
      </Group>
      <div className="two-columns" data-even>
        <Panel
          title={
            <>
              <Box component="span" visibleFrom="xs">
                {_('Lottery description and date')}
              </Box>
              <Box component="span" hiddenFrom="xs">
                {_('Description and date')}
              </Box>
            </>
          }
          icon={IconCalendarEvent}
          data-tour="lottery.schedule"
        >
          <Form onSubmit={saveSchedule}>
            <DateTimeField
              label={_('Date')}
              description={_('The earliest it can run')}
              disabled={Boolean(status)}
              {...dateTime(schedule, 'start_date')}
            />
            <Textarea
              label={_('Description')}
              autosize
              minRows={3}
              disabled={Boolean(status)}
              {...field(schedule, 'description')}
            />
            <Group justify="flex-end">
              <Button type="submit" loading={schedule.processing} disabled={Boolean(status)}>
                {_('Save')}
              </Button>
            </Group>
          </Form>
        </Panel>

        <Panel
          title={_('Readiness')}
          icon={IconListCheck}
          tone="positive"
          data-tour="lottery.readiness"
        >
          <Stack gap="xs">
            {readiness.map((type) => {
              const ready = type.units_count === type.families_count;
              return (
                <Group key={type.id} gap="sm" wrap="nowrap">
                  {ready ? (
                    <IconCircleCheckFilled size={20} className="text-positive" />
                  ) : (
                    <IconAlertCircle size={20} color="var(--mtav-warning)" />
                  )}
                  <Text style={{ flex: 1 }} fw={550}>
                    {type.name}
                  </Text>
                  <Text className="ds-caption">
                    {[
                      _n('{count} unit', '{count} units', type.units_count),
                      _n('{count} family', '{count} families', type.families_count),
                    ].join(' · ')}
                  </Text>
                </Group>
              );
            })}
            {!readiness.length ? (
              <Text c="dimmed">{_('Add unit types, units and families first.')}</Text>
            ) : null}
          </Stack>

          <div className="panel-actions" style={{ paddingTop: 16 }}>
            {status === 'running' ? (
              <Alert color="blue" radius="lg" icon={<Loader size={18} />}>
                {_('The lottery is running.')}
              </Alert>
            ) : status === 'completed' ? (
              <Alert color="green" radius="lg" icon={<IconCircleCheckFilled size={20} />}>
                {_('The lottery is done: every family has its unit.')}
              </Alert>
            ) : (
              <Stack gap="sm">
                {!due ? (
                  <Text className="ds-caption">
                    {lottery.start_date
                      ? _('It can run once its date arrives.')
                      : _('Set its date first.')}
                  </Text>
                ) : !balanced ? (
                  <Text size="sm" c="var(--mtav-warning)">
                    {_(
                      'Some types have more units than families (or fewer): you will be asked to confirm.',
                    )}
                  </Text>
                ) : null}
                <Button
                  fullWidth
                  size="md"
                  disabled={!due || !readiness.length || !runnable}
                  leftSection={<IconDice5 size={20} data-tour="lottery.run" />}
                  onClick={() => run()}
                >
                  {_('Run the lottery')}
                </Button>
              </Stack>
            )}
          </div>
        </Panel>
      </div>

      {runs.length ? (
        <div style={{ marginTop: 24 }} data-tour="lottery.runs">
          <Panel title={_('Runs')} icon={IconHistory} tone="neutral" flush>
            {runs.map((item) => (
              <Group key={item.id} className="row" align="flex-start" wrap="wrap">
                <Stack gap={4} style={{ flex: 1, minWidth: 220 }}>
                  <Group gap="sm">
                    <Status tone={TONES[item.status]} pill live={item.status === 'running'}>
                      {labels[item.status]}
                    </Status>
                    <Text className="ds-caption">
                      <DateTime value={item.started_at} />
                      {item.started_by ? ` · ${_('by {name}', { name: item.started_by })}` : null}
                    </Text>
                  </Group>
                  {item.failure ? (
                    <Text size="sm" c="red">
                      {item.failure}
                    </Text>
                  ) : null}
                  {item.invalidation_reason ? (
                    <Text size="sm">
                      {_('Invalidated by {name}:', { name: item.invalidated_by ?? '?' })} «
                      {item.invalidation_reason}»
                    </Text>
                  ) : null}
                  {item.status === 'completed' || item.status === 'invalidated' ? (
                    <Anchor
                      component={Link}
                      href={route('lottery.record', { lotteryRun: item.id })}
                      size="sm"
                      fw={650}
                    >
                      {_('Its record')}
                    </Anchor>
                  ) : null}
                </Stack>
                {item.status === 'completed' ? (
                  <Button
                    variant="default"
                    color="red"
                    c="red"
                    leftSection={<IconArrowBackUp size={18} />}
                    onClick={() => invalidate(item)}
                  >
                    {_('Invalidate')}
                  </Button>
                ) : null}
              </Group>
            ))}
          </Panel>
        </div>
      ) : null}
    </section>
  );
}

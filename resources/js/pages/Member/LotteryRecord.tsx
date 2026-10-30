import { DateTime } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { useCurrentPage } from '@/components/layout/page';
import { Panel, Stat, Status } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Badge, Button, Code, Group, SimpleGrid, Text } from '@mantine/core';
import {
  IconDownload,
  IconFingerprint,
  IconListNumbers,
  IconUsersGroup,
} from '@tabler/icons-react';

interface Props {
  run: {
    id: number;
    status: 'completed' | 'invalidated';
    started_at: string;
    finished_at: string | null;
    by: string | null;
    solver: string;
  };
  families: {
    id: number;
    name: string;
    unit: { id: number; identifier: string } | null;
    place: number | null;
    of: number;
  }[];
  worst: number | null;
  average: number | null;
  fingerprint: string;
  /** The viewer's family: its whole ranking as the lottery read it */
  own: { id: number; ranking: string[] } | null;
}

/**
 * A lottery run's record, for anyone in the project to check: each family's unit and its
 * place in the family's ranking as recorded when the lottery ran, the worst and the
 * average, and the record's fingerprint. Whole rankings stay with their families.
 */
export default function LotteryRecord({ run, families, worst, average, fingerprint, own }: Props) {
  const manages = Boolean(useCurrentPage().props.project?.can.manage);

  return (
    <>
      <PageHeader
        title={_('The lottery’s record')}
        description={_('What it decided, and from what: written as it ran, never changed.')}
        back={{ href: route('lottery.show'), label: _('Lottery') }}
        help={
          <InfoTip title={_('Checking the result')}>
            {_(
              'Each family’s place is where its unit stood in the ranking the family had when the lottery ran: 1 is its first choice. The lottery first made the worst place as good as it could, then the places as good as possible for everyone.',
            )}
          </InfoTip>
        }
        actions={
          manages ? (
            <Button
              component="a"
              href={route('lottery.download', { lotteryRun: run.id })}
              variant="default"
              leftSection={<IconDownload size={18} />}
            >
              {_('Download the full record')}
            </Button>
          ) : null
        }
      />

      <Group gap="sm" mb="lg">
        {run.status === 'invalidated' ? (
          <Status tone="warning" pill>
            {_('Invalidated')}
          </Status>
        ) : (
          <Status tone="positive" pill>
            {_('Final')}
          </Status>
        )}
        <Text className="ds-caption">
          <DateTime value={run.finished_at ?? run.started_at} />
          {run.by ? ` · ${_('run by {name}', { name: run.by })}` : null}
        </Text>
      </Group>

      <SimpleGrid cols={{ base: 1, xs: 3 }} mb="lg">
        <Stat label={_('Families')} value={families.length} icon={IconUsersGroup} />
        <Stat
          label={_('Worst place')}
          value={worst ?? '—'}
          note={_('1 is a first choice')}
          icon={IconListNumbers}
          tone="accent"
        />
        <Stat
          label={_('Average place')}
          value={
            average === null ? '—' : average.toLocaleString(undefined, { maximumFractionDigits: 2 })
          }
          icon={IconListNumbers}
          tone="info"
        />
      </SimpleGrid>

      <div className="two-columns">
        <Panel title={_('Each family')} icon={IconUsersGroup} flush>
          {families.map((family) => (
            <Group
              key={family.id}
              className="row"
              justify="space-between"
              wrap="nowrap"
              data-own={family.id === own?.id || undefined}
            >
              <Text fw={600} className="ds-truncate">
                {family.name}
              </Text>
              <Group gap="sm" wrap="nowrap">
                <Badge color={family.unit ? 'green' : 'gray'}>
                  {family.unit?.identifier ?? _('No unit')}
                </Badge>
                <Text className="ds-caption" miw={84} ta="right">
                  {family.place
                    ? _('{place} of {count}', { place: family.place, count: family.of })
                    : '—'}
                </Text>
              </Group>
            </Group>
          ))}
        </Panel>

        <div>
          {own ? (
            <Panel
              title={_('Your family’s ranking, as recorded')}
              icon={IconListNumbers}
              style={{ marginBottom: 24 }}
            >
              <ol className="record-ranking">
                {own.ranking.map((identifier) => (
                  <li key={identifier}>{identifier}</li>
                ))}
              </ol>
            </Panel>
          ) : null}
          <Panel title={_('Fingerprint')} icon={IconFingerprint} tone="neutral">
            <Text size="sm" c="dimmed" mb="sm">
              {_(
                'Computed from everything the lottery read and decided. Note it down: if the record were ever changed, it would no longer match.',
              )}
            </Text>
            <Code block style={{ wordBreak: 'break-all', whiteSpace: 'normal' }}>
              {fingerprint}
            </Code>
          </Panel>
        </div>
      </div>
    </>
  );
}

import { DateTime } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Panel, Stat, Status } from '@/design';
import { decimal } from '@/lib/app';
import { route } from '@/lib/route';
import { _, _nodes } from '@/lib/translations';
import { Accordion, Anchor, Badge, Button, Code, Group, SimpleGrid, Text } from '@mantine/core';
import {
  IconDownload,
  IconFingerprint,
  IconListNumbers,
  IconScale,
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
    /** When every family's ranking becomes everyone's (null: never, for a run that did not stand) */
    discloses_at: string | null;
    disclosed: boolean;
  };
  families: {
    id: number;
    name: string;
    unit: { id: number; identifier: string } | null;
    place: number | null;
    of: number;
    /** The family's whole ranking as recorded, once the run is disclosed */
    ranking: string[] | null;
  }[];
  worst: number | null;
  average: number | null;
  fingerprint: string;
  /** The viewer's family: its whole ranking as the lottery read it */
  own: { id: number; ranking: string[] } | null;
  /** Whether the whole record is theirs to download: the admins' from the start, everyone's once disclosed */
  canDownload: boolean;
}

/**
 * A lottery run's record, for anyone in the project to check: each family's unit and its
 * place in the family's ranking as recorded when the lottery ran, the worst and the
 * average, and the record's fingerprint. Rankings stay with their families until the run
 * is disclosed, a while after it completes; then every ranking is here for anyone to check
 * the result against, and the whole record is anyone's to download.
 */
export default function LotteryRecord({
  run,
  families,
  worst,
  average,
  fingerprint,
  own,
  canDownload,
}: Props) {
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
          <Group gap="xs">
            <Button
              component="a"
              href={route('lottery.models')}
              variant="subtle"
              leftSection={<IconDownload size={18} />}
            >
              {_('The models')}
            </Button>
            {canDownload ? (
              <Button
                component="a"
                href={route('lottery.audit', { lotteryRun: run.id })}
                variant="default"
                leftSection={<IconDownload size={18} />}
              >
                {_('Download the full record')}
              </Button>
            ) : null}
          </Group>
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
          value={average === null ? '—' : decimal(average, 2)}
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
              <Text fw={600} className="ds-truncate" title={family.name}>
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
          {run.disclosed ? (
            <Panel
              title={_('Every family’s ranking, as recorded')}
              icon={IconListNumbers}
              style={{ marginBottom: 24 }}
            >
              <Text size="sm" c="dimmed" mb="sm">
                {_nodes('Public since {date}, so that anyone can check the result.', {
                  date: <DateTime value={run.discloses_at} />,
                })}
              </Text>
              <Accordion variant="separated" radius="md" defaultValue={own ? String(own.id) : null}>
                {families.map((family) => (
                  <Accordion.Item key={family.id} value={String(family.id)}>
                    <Accordion.Control fw={600}>{family.name}</Accordion.Control>
                    <Accordion.Panel>
                      <ol className="record-ranking">
                        {(family.ranking ?? []).map((identifier) => (
                          <li key={identifier}>{identifier}</li>
                        ))}
                      </ol>
                    </Accordion.Panel>
                  </Accordion.Item>
                ))}
              </Accordion>
            </Panel>
          ) : (
            <>
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
              {run.discloses_at ? (
                <Text size="sm" c="dimmed" mb="lg">
                  {_nodes(
                    'Every family’s ranking becomes public on {date}, so that anyone can check the result, unless the run is invalidated before.',
                    { date: <DateTime value={run.discloses_at} /> },
                  )}
                </Text>
              ) : null}
            </>
          )}

          <Panel
            title={_('How the lottery decides')}
            icon={IconScale}
            tone="info"
            style={{ marginBottom: 24 }}
          >
            <Text size="sm" c="dimmed">
              {_(
                'Each family ranks the units of its type. The lottery first finds the best place it can give the family that ends up worst off, then, keeping that, the best places for everyone together. Where rankings tie, the order of the data decides. It is solved with GLPK, free software, on two models anyone may read and run again.',
              )}{' '}
              <Anchor href={route('lottery.models')} size="sm">
                {_('The models')}
              </Anchor>
            </Text>
          </Panel>

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

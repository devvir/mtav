import { Stat } from '@/design';
import { SimpleGrid } from '@mantine/core';
import { IconCoin, IconServer2, IconStack2 } from '@tabler/icons-react';
import type { Readings } from '../../api';
import { money } from '../../format';
import { ENVIRONMENTS, onAws } from './environments';

/** Deployment at a glance: environments on AWS, servers running, credits left, this month's spend */
export function Figures({ readings }: { readings?: Readings }) {
  const cloud = readings?.cloud.data ?? [];
  const plan = readings?.money.data?.plan;
  const month = readings?.money.data?.month;

  return (
    <SimpleGrid cols={{ base: 1, sm: 2, lg: 4 }} mb="lg">
      <Stat
        label="Environments on AWS"
        value={`${onAws(cloud).length} of ${ENVIRONMENTS.length}`}
        icon={IconStack2}
        tone="brand"
      />
      <Stat
        label="Servers running"
        value={
          readings?.cloud.data
            ? `${cloud.filter((one) => one.state === 'running').length} of ${cloud.length}`
            : '—'
        }
        icon={IconServer2}
        tone="info"
      />
      <Stat
        label="Credits left"
        value={plan?.credits != null ? money(plan.credits) : '—'}
        icon={IconCoin}
        tone="positive"
        note={
          plan
            ? `${plan.type.toLowerCase()} plan, until ${new Date(plan.until).toLocaleDateString()}`
            : undefined
        }
      />
      <Stat
        label="This month"
        value={month ? money(month.spent) : '—'}
        icon={IconCoin}
        tone={month && month.spent > month.limit ? 'negative' : 'accent'}
        note={
          month
            ? `of a ${money(month.limit)} budget${month.forecast ? `, ${money(month.forecast)} forecast` : ''}`
            : undefined
        }
      />
    </SimpleGrid>
  );
}

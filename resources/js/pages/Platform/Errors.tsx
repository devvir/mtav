import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { router } from '@inertiajs/react';
import { Badge, Button, Card, Code, Collapse, Group, Stack, Text } from '@mantine/core';
import { IconCheck, IconMoodSmile } from '@tabler/icons-react';
import { useState } from 'react';

interface ErrorEntry {
  id: number;
  class: string;
  message: string;
  file: string | null;
  line: number | null;
  url: string | null;
  trace: string | null;
  count: number;
  first_seen_at: string;
  last_seen_at: string;
}

/** An error: what and where, how often and since when; its trace on demand */
function ErrorCard({ error }: { error: ErrorEntry }) {
  const [open, setOpen] = useState(false);

  return (
    <Card p="md" data-tour="errors.list">
      <Group justify="space-between" wrap="nowrap" align="flex-start">
        <div style={{ minWidth: 0 }}>
          <Text fw={700} className="ds-truncate">
            {error.class.split('\\').pop()}
          </Text>
          <Text size="sm" style={{ overflowWrap: 'anywhere' }}>
            {error.message}
          </Text>
          <Text className="ds-caption" mt={4} style={{ overflowWrap: 'anywhere' }}>
            {error.file}:{error.line}
            {error.url ? ` · ${error.url}` : null}
          </Text>
          <Text className="ds-caption">
            {_('Last')} <DateTime value={error.last_seen_at} relative /> · {_('first')}{' '}
            <DateTime value={error.first_seen_at} time={false} />
          </Text>
        </div>
        <Stack gap="xs" align="flex-end">
          <Badge color={error.count > 10 ? 'red' : 'yellow'} variant="light">
            {_n('{count} time', '{count} times', error.count)}
          </Badge>
          <Button
            size="xs"
            variant="default"
            leftSection={<IconCheck size={14} />}
            onClick={() =>
              router.delete(route('admin.errors.destroy', error.id), { preserveScroll: true })
            }
          >
            {_('Solved')}
          </Button>
        </Stack>
      </Group>
      {error.trace ? (
        <>
          <Button variant="subtle" size="xs" mt="xs" px={0} onClick={() => setOpen(!open)}>
            {open ? _('Hide the trace') : _('Show the trace')}
          </Button>
          <Collapse expanded={open} transitionDuration={220}>
            <Code block mt="xs" style={{ maxHeight: 320, overflow: 'auto' }}>
              {error.trace}
            </Code>
          </Collapse>
        </>
      ) : null}
    </Card>
  );
}

/**
 * What went wrong in the app, latest first: each error once, counted. Marked solved, it
 * goes (it comes back if it happens again).
 */
export default function Errors({ errors }: { errors: Paginated<ErrorEntry> }) {
  return (
    <>
      <PageHeader
        title={_('Errors')}
        description={_('What went wrong in the app, latest first.')}
        help={
          <InfoTip title={_('Errors')}>
            {_(
              'Each error once, with how often it happened. The owner is emailed when a new one appears. Mark it solved once fixed: it comes back if it happens again.',
            )}
          </InfoTip>
        }
      />
      {errors.data.length ? (
        <Stack gap="sm">
          {errors.data.map((error) => (
            <ErrorCard key={error.id} error={error} />
          ))}
        </Stack>
      ) : (
        <EmptyState icon={IconMoodSmile} title={_('No errors')} />
      )}
      <Pagination page={errors} />
    </>
  );
}

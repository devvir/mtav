import { _ } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { Link } from '@inertiajs/react';
import { ActionIcon, Group, Text } from '@mantine/core';
import { IconChevronLeft, IconChevronRight } from '@tabler/icons-react';

/** Where a long list is: which ones are shown, and the pages before and after */
export function Pagination({ page }: { page: Paginated<unknown> }) {
  if (page.meta.last_page <= 1) return null;

  return (
    <Group component="nav" justify="space-between" pt="md" aria-label={_('Pages')}>
      <Text className="ds-caption ds-num">
        {_('{from}–{to} of {total}', {
          from: page.meta.from ?? 0,
          to: page.meta.to ?? 0,
          total: page.meta.total,
        })}
      </Text>
      <Group gap="xs">
        <ActionIcon
          component={Link}
          href={page.links.prev ?? ''}
          preserveScroll
          variant="default"
          size="lg"
          disabled={!page.links.prev}
          aria-label={_('Previous page')}
        >
          <IconChevronLeft size={18} />
        </ActionIcon>
        <ActionIcon
          component={Link}
          href={page.links.next ?? ''}
          preserveScroll
          variant="default"
          size="lg"
          disabled={!page.links.next}
          aria-label={_('Next page')}
        >
          <IconChevronRight size={18} />
        </ActionIcon>
      </Group>
    </Group>
  );
}

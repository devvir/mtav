import { _ } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { ActionIcon, Group, Text } from '@mantine/core';
import { IconChevronLeft, IconChevronRight } from '@tabler/icons-react';
import { useEffect } from 'react';

/** Where a long list is: which ones are shown (of how many, if counted), the pages around it */
export function Pagination({ page }: { page: Paginated<unknown> }) {
  // Past the end (a page typed, or its last ones removed meanwhile): the last one there is instead
  const pastEnd = !page.data.length && page.meta.current_page > 1;
  const last = page.links.last ?? page.links.first;
  useEffect(() => {
    if (pastEnd && last)
      router.get(last, {}, { replace: true, preserveState: true, preserveScroll: true });
  }, [pastEnd, last]);

  if (!page.links.prev && !page.links.next) return null;

  return (
    <Group component="nav" justify="space-between" pt="md" aria-label={_('Pages')}>
      <Text className="ds-caption ds-num">
        {page.meta.total === undefined
          ? `${page.meta.from ?? 0}–${page.meta.to ?? 0}`
          : _('{from}–{to} of {total}', {
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

import { DateTime } from '@/components/feedback/DateTime';
import { EmptyState } from '@/design';
import { setFilter } from '@/lib/listFilters';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { Button, Card, Group, Switch, Text } from '@mantine/core';
import { IconArchive } from '@tabler/icons-react';
import type { ReactNode } from 'react';

export interface ArchivedItem {
  id: number;
  title: string;
  deleted_at: string;
  /** For a family: its history */
  created_at?: string;
  people?: string[];
}

export interface ArchiveState {
  count: number;
  shown: boolean;
  items: ArchivedItem[] | null;
}

/** For admins, where something was archived: whether the page shows that instead */
export function ArchiveSwitch({ archive }: { archive?: ArchiveState | null }) {
  if (!archive || (!archive.count && !archive.shown)) return null;

  return (
    <Switch
      label={_('Show archived ({count})', { count: archive.count })}
      checked={archive.shown}
      onChange={(event) => setFilter('archived', event.currentTarget.checked)}
    />
  );
}

/** What was removed and kept, each to bring back */
export function ArchivedList({
  archive,
  kind,
  action,
}: {
  archive: ArchiveState;
  kind: string;
  /** Instead of restoring: how this kind comes back */
  action?: (item: ArchivedItem) => ReactNode;
}) {
  if (!archive.items?.length) {
    return <EmptyState icon={IconArchive} title={_('Nothing archived here')} />;
  }

  return (
    <Card p={0}>
      {archive.items.map((item) => (
        <Group key={item.id} className="row">
          <div style={{ flex: '1 1 200px', minWidth: 0 }}>
            <Text fw={600} className="ds-truncate">
              {item.title}
            </Text>
            <Text className="ds-caption">
              {_('Archived')} <DateTime value={item.deleted_at} time={false} />
            </Text>
          </div>
          {action ? (
            action(item)
          ) : (
            <Button
              variant="default"
              onClick={() =>
                router.put(
                  route('manage.archive.restore', { kind, id: item.id }),
                  {},
                  { preserveScroll: true },
                )
              }
            >
              {_('Restore')}
            </Button>
          )}
        </Group>
      ))}
    </Card>
  );
}

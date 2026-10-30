import { DateTime } from '@/components/feedback/DateTime';
import { EmptyState } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { Button, Card, Group, Switch, Text } from '@mantine/core';
import { IconArchive } from '@tabler/icons-react';

export interface ArchiveState {
  count: number;
  shown: boolean;
  items: { id: number; title: string; deleted_at: string }[] | null;
}

/** For admins, where something was archived: whether the page shows that instead */
export function ArchiveSwitch({ archive }: { archive?: ArchiveState | null }) {
  if (!archive || (!archive.count && !archive.shown)) return null;

  const toggle = (on: boolean) => {
    const url = new URL(window.location.href);
    if (on) url.searchParams.set('archived', '1');
    else url.searchParams.delete('archived');
    url.searchParams.delete('page');
    router.get(
      url.pathname + url.search,
      {},
      { preserveState: true, preserveScroll: true, replace: true },
    );
  };

  return (
    <Switch
      label={_('Show archived ({count})', { count: archive.count })}
      checked={archive.shown}
      onChange={(event) => toggle(event.currentTarget.checked)}
    />
  );
}

/** What was removed and kept, each to bring back */
export function ArchivedList({ archive, kind }: { archive: ArchiveState; kind: string }) {
  if (!archive.items?.length) {
    return <EmptyState icon={IconArchive} title={_('Nothing archived here')} />;
  }

  return (
    <Card p={0}>
      {archive.items.map((item) => (
        <Group key={item.id} className="row" wrap="nowrap">
          <div style={{ flex: 1, minWidth: 0 }}>
            <Text fw={600} className="ds-truncate">
              {item.title}
            </Text>
            <Text className="ds-caption">
              {_('Archived')} <DateTime value={item.deleted_at} time={false} />
            </Text>
          </div>
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
        </Group>
      ))}
    </Card>
  );
}

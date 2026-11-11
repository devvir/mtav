import { _ } from '@/lib/translations';
import { Button, Group, Loader } from '@mantine/core';
import { IconCloudCheck, IconCloudOff } from '@tabler/icons-react';
import type { SaveStatus } from './autosave';

/**
 * Where saving is: quiet when all is saved, a retry when it could not be, and why the server
 * refused it (`reason`)
 */
export function SaveState({
  status,
  reason,
  onRetry,
}: {
  status: SaveStatus;
  reason?: string | null;
  onRetry: () => void;
}) {
  if (status === 'failed')
    return (
      <Group
        key={reason ?? ''}
        gap={6}
        c="var(--mtav-warning)"
        fz="sm"
        fw={600}
        role="status"
        className="plan-save-state"
        wrap="nowrap"
      >
        <IconCloudOff size={16} style={{ flex: 'none' }} />
        <span>{reason || _('Not saved yet')}</span>
        <Button variant="subtle" size="compact-xs" onClick={onRetry} style={{ flex: 'none' }}>
          {_('Try again')}
        </Button>
      </Group>
    );

  return (
    <Group gap={6} className="ds-caption" role="status" aria-live="polite">
      {status === 'saved' ? <IconCloudCheck size={16} /> : <Loader size={12} />}
      {status === 'saved' ? _('Saved') : _('Saving…')}
    </Group>
  );
}

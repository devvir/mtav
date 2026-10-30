import { useOnline } from '@/lib/offline';
import { _ } from '@/lib/translations';
import { Tooltip } from '@mantine/core';
import { IconCloudOff } from '@tabler/icons-react';

/** In the bar while the server cannot be reached: what is shown is what was last loaded */
export function OfflineStatus() {
  const online = useOnline();
  if (online) return null;

  return (
    <Tooltip
      label={_('You see what was last loaded; changes wait until the connection is back.')}
      multiline
      w={260}
    >
      <span className="shell-offline" role="status">
        <IconCloudOff size={16} />
        {_('Offline')}
      </span>
    </Tooltip>
  );
}

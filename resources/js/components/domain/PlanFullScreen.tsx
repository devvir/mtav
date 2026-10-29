import { _ } from '@/lib/translations';
import { ActionIcon, Tooltip } from '@mantine/core';
import { IconMaximize, IconMinimize } from '@tabler/icons-react';
import { useEffect, useState } from 'react';

/**
 * A plan's panel over the whole window, to draw or look at it comfortably: the same panel,
 * never another copy of it (so the editor keeps all it holds). Escape, or the button, brings
 * it back; Escape first lets go of what the plan itself takes it for (a selection).
 */
export function usePlanFullScreen() {
  const [full, setFull] = useState(false);

  useEffect(() => {
    if (!full) return undefined;
    document.documentElement.dataset.planFull = '';
    const onKey = (event: KeyboardEvent) => {
      if (event.key !== 'Escape' || (event.target as Element).closest?.('[role="dialog"]')) return;
      // Once every other listener had its say: the editor marks the Escape it used
      window.setTimeout(() => !event.defaultPrevented && setFull(false));
    };
    window.addEventListener('keydown', onKey);
    return () => {
      delete document.documentElement.dataset.planFull;
      window.removeEventListener('keydown', onKey);
    };
  }, [full]);

  const label = full ? _('Leave full screen') : _('Full screen');

  return {
    full,
    /** What the panel takes: its look over the window */
    panel: { 'data-full': full || undefined },
    button: (
      <Tooltip label={label}>
        <ActionIcon
          variant="subtle"
          color="gray"
          size="lg"
          aria-label={label}
          aria-pressed={full}
          onClick={() => setFull(!full)}
        >
          {full ? <IconMinimize size={18} /> : <IconMaximize size={18} />}
        </ActionIcon>
      </Tooltip>
    ),
  };
}

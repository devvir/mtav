import { _ } from '@/lib/translations';
import { ActionIcon, Tooltip } from '@mantine/core';
import { IconMaximize, IconMinimize } from '@tabler/icons-react';
import { useEffect, useState } from 'react';

/**
 * A plan's panel over the whole window, to draw or look at it comfortably: the same panel,
 * never another copy of it (so the editor keeps all it holds). Escape, or the button, brings
 * it back, as everywhere (what is open over it, a dialog, a menu, closes first).
 */
export function usePlanFullScreen() {
  const [full, setFull] = useState(false);

  useEffect(() => {
    if (!full) return undefined;
    document.documentElement.dataset.planFull = '';
    const onKey = (event: KeyboardEvent) => {
      if (event.key !== 'Escape') return;
      // What is open over the plan closes first (a dialog, a menu, a field being typed in)
      const inside = (event.target as Element).closest?.(
        '[role="dialog"], [role="menu"], [role="listbox"], .mantine-Popover-dropdown, input, textarea, select',
      );
      if (!inside) setFull(false);
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

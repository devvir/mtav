import { Panel } from '@/design';
import { _ } from '@/lib/translations';
import { ActionIcon, Collapse } from '@mantine/core';
import { useLocalStorage } from '@mantine/hooks';
import { IconChevronDown } from '@tabler/icons-react';
import type { ComponentProps, ReactNode } from 'react';

/**
 * A panel its reader can fold, remembered on their device by `name`: what they do not
 * need out of the way, the same for every panel that folds.
 */
export function FoldablePanel({
  name,
  actions,
  flush,
  children,
  ...panel
}: { name: string; children: ReactNode } & ComponentProps<typeof Panel>) {
  const [open, setOpen] = useLocalStorage({ key: `mtav.open.${name}`, defaultValue: true });

  return (
    // The body pads inside what folds: folded, nothing is left under the title
    <Panel
      {...panel}
      flush
      data-folded={open ? undefined : true}
      actions={
        <>
          {open ? actions : null}
          <ActionIcon
            variant="subtle"
            aria-expanded={open}
            aria-label={open ? _('Fold') : _('Unfold')}
            onClick={() => setOpen(!open)}
          >
            <IconChevronDown
              size={18}
              style={{
                transform: open ? 'rotate(180deg)' : undefined,
                transition: 'transform 200ms',
              }}
            />
          </ActionIcon>
        </>
      }
    >
      <Collapse expanded={open} transitionDuration={220}>
        <div className={flush ? undefined : 'ds-panel-pad'}>{children}</div>
      </Collapse>
    </Panel>
  );
}

import { Glyph, Panel } from '@/design';
import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';
import { ActionIcon, Collapse } from '@mantine/core';
import { useLocalStorage } from '@mantine/hooks';
import { IconArrowUpRight, IconChevronDown } from '@tabler/icons-react';
import type { ComponentProps, ReactNode } from 'react';

/**
 * A panel its reader can fold, remembered on their device by `name`: what they do not
 * need out of the way, the same for every panel that folds. Given `href`, its title (icon and
 * all, an arrow after) leads to the page it shows a part of.
 */
export function FoldablePanel({
  name,
  actions,
  flush,
  href,
  children,
  ...panel
}: { name: string; href?: string; children: ReactNode } & ComponentProps<typeof Panel>) {
  const [open, setOpen] = useLocalStorage({ key: `mtav.open.${name}`, defaultValue: true });

  return (
    // The body pads inside what folds: folded, nothing is left under the title
    <Panel
      {...panel}
      icon={href ? undefined : panel.icon}
      title={
        href ? (
          <Link href={href} className="panel-title-link">
            {panel.icon ? <Glyph icon={panel.icon} tone={panel.tone ?? 'brand'} size={30} /> : null}
            <span>{panel.title}</span>
            <IconArrowUpRight size={18} className="panel-title-arrow" aria-hidden />
          </Link>
        ) : (
          panel.title
        )
      }
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

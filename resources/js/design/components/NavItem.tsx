import type { Icon } from '@tabler/icons-react';
import { forwardRef, type ComponentPropsWithoutRef, type ElementType, type ReactNode } from 'react';

interface Own {
  /** What it is drawn as: a router's link, a button (an anchor by default) */
  as?: ElementType;
  icon: Icon;
  label: ReactNode;
  active?: boolean;
  /** Something waiting there */
  count?: number;
}

/** A place in an area's navigation: its icon and name, lit when one is there */
export const NavItem = forwardRef<
  HTMLElement,
  Own & Omit<ComponentPropsWithoutRef<'a'>, keyof Own>
>(function NavItem(
  { as: Element = 'a', icon: Shape, label, active, count, className, ...rest },
  ref,
) {
  return (
    <Element
      ref={ref}
      className={className ?? 'ds-nav-item'}
      data-active={active || undefined}
      aria-current={active ? 'page' : undefined}
      {...rest}
    >
      <Shape size={20} stroke={1.8} style={{ flex: 'none' }} />
      <span className="ds-truncate" style={{ flex: 1 }}>
        {label}
      </span>
      {count ? <span className="ds-count">{count}</span> : null}
    </Element>
  );
});

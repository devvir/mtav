import type { Icon } from '@tabler/icons-react';
import type { ReactNode } from 'react';
import { Glyph } from './Glyph';

/** Where something will be, while there is nothing yet: what goes here, and how to start */
export function EmptyState({
  icon,
  title,
  children,
  action,
}: {
  icon: Icon;
  title: ReactNode;
  children?: ReactNode;
  action?: ReactNode;
}) {
  return (
    <div className="ds-empty">
      <Glyph icon={icon} tone="neutral" size={48} />
      <p className="ds-empty-title">{title}</p>
      {children ? <p className="ds-caption ds-empty-text">{children}</p> : null}
      {action}
    </div>
  );
}

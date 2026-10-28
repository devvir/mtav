import type { Icon } from '@tabler/icons-react';
import type { ReactNode } from 'react';
import type { Tone } from '../tone';
import { Glyph } from './Glyph';

/**
 * A titled part of a page: its icon, title and a word on it, actions on the right, and its
 * content (flush, for a table or a list that draws its own edges).
 */
export function Panel({
  title,
  icon,
  tone = 'brand',
  aside,
  actions,
  flush,
  className,
  children,
  ...rest
}: {
  title: ReactNode;
  icon?: Icon;
  tone?: Tone;
  aside?: ReactNode;
  actions?: ReactNode;
  flush?: boolean;
  children: ReactNode;
} & Omit<React.HTMLAttributes<HTMLElement>, 'title'>) {
  return (
    <section className={className ? `ds-panel ${className}` : 'ds-panel'} {...rest}>
      <header className="ds-panel-header">
        {icon ? <Glyph icon={icon} tone={tone} size={30} /> : null}
        <h2 className="ds-panel-title">{title}</h2>
        {aside ? (
          <span
            className="ds-caption ds-truncate"
            style={{ minWidth: 0 }}
            title={typeof aside === 'string' ? aside : undefined}
          >
            {aside}
          </span>
        ) : null}
        {actions ? (
          <div style={{ marginLeft: 'auto', display: 'flex', gap: 8 }}>{actions}</div>
        ) : null}
      </header>
      <div className="ds-panel-body" data-flush={flush || undefined}>
        {children}
      </div>
    </section>
  );
}

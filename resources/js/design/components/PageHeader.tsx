import type { ReactNode } from 'react';

/** A page's head: where it comes from, its title (and a help mark), a line on it, its actions */
export function PageHeader({
  title,
  description,
  help,
  back,
  actions,
}: {
  title: ReactNode;
  description?: ReactNode;
  help?: ReactNode;
  back?: ReactNode;
  actions?: ReactNode;
}) {
  return (
    <header className="ds-page-header">
      {back ? <div className="ds-page-back">{back}</div> : null}
      <div className="ds-page-head">
        <div style={{ minWidth: 0 }}>
          <h1 className="ds-page-title">
            {title}
            {help}
          </h1>
          {description ? <p className="ds-page-description">{description}</p> : null}
        </div>
        {actions ? <div className="ds-page-actions">{actions}</div> : null}
      </div>
    </header>
  );
}

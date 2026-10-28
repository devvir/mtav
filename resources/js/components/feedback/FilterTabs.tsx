import { Link } from '@inertiajs/react';

/** Which part of a list is shown: one pill each, the chosen one lit, a count where something waits */
export function FilterTabs({
  items,
  ...rest
}: {
  items: { label: string; href: string; active: boolean; count?: number }[];
} & React.HTMLAttributes<HTMLElement>) {
  return (
    <nav className="ds-pills" {...rest}>
      {items.map((item) => (
        <Link
          key={item.href}
          href={item.href}
          className="ds-pill"
          data-active={item.active || undefined}
          aria-current={item.active ? 'page' : undefined}
          preserveScroll
        >
          {item.label}
          {item.count ? <span className="ds-count">{item.count}</span> : null}
        </Link>
      ))}
    </nav>
  );
}

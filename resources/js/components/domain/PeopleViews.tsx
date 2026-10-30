import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';

/**
 * The cooperative's people, two ways: by family, or everyone (admins too). One place in
 * the navigation; each view keeps its own address, search and details.
 */
export function PeopleViews() {
  const views = [
    { label: _('Families'), href: route('families.index'), active: routeIs('families.*') },
    { label: _('Everyone'), href: route('people.index'), active: routeIs('people.*') },
  ];

  return (
    <nav className="people-views" aria-label={_('How to see them')} data-tour="people.views">
      {views.map((view) => (
        <Link
          key={view.label}
          href={view.href}
          className="ds-pill floor-tab"
          data-active={view.active || undefined}
          aria-current={view.active ? 'page' : undefined}
        >
          {view.label}
        </Link>
      ))}
    </nav>
  );
}

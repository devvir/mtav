import { route, routeIs } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import { Link, usePage } from '@inertiajs/react';
import { IconGauge, IconMail, IconSettings } from '@tabler/icons-react';

/**
 * The parts of a project's settings, for its admins: setting it up, inviting its people,
 * and the project itself. Each is its own page (its own address); these lead between them.
 */
export function SettingsTabs() {
  const pending = usePage().props.project?.pending_invitations ?? 0;
  const tabs = [
    {
      label: _('Setup'),
      href: route('manage.overview'),
      icon: IconGauge,
      active: routeIs('manage.overview'),
    },
    {
      label: _('Invitations'),
      href: route('manage.invitations.index'),
      icon: IconMail,
      active: routeIs('manage.invitations.*'),
      count: pending,
    },
    {
      label: _('Project'),
      href: route('manage.settings.edit'),
      icon: IconSettings,
      active: routeIs('manage.settings.*'),
    },
  ];

  return (
    <nav className="settings-tabs" aria-label={_('Settings')}>
      {tabs.map(({ label, href, icon: Icon, active, count }) => (
        <Link
          key={href}
          href={href}
          prefetch
          className="settings-tab"
          aria-current={active ? 'page' : undefined}
        >
          <Icon size={18} />
          {label}
          {count ? (
            <span
              className="settings-tab-count"
              aria-label={_n('{count} invitation pending', '{count} invitations pending', count)}
            >
              {count}
            </span>
          ) : null}
        </Link>
      ))}
    </nav>
  );
}

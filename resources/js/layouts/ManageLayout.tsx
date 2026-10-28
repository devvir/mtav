import { AppShell, type NavEntry } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';
import {
  IconAlertTriangle,
  IconArrowLeft,
  IconCalendarEvent,
  IconDice5,
  IconGauge,
  IconLayoutGrid,
  IconMail,
  IconMap,
  IconSettings,
  IconStack2,
  IconUsers,
  IconUsersGroup,
} from '@tabler/icons-react';
import type { ReactNode } from 'react';

/** The project admin area: configuring a project */
export function ManageLayout({ children }: { children: ReactNode }) {
  const project = useCurrentPage().props.project!;

  const nav: NavEntry[] = [
    {
      label: _('Overview'),
      href: route('manage.overview'),
      icon: IconGauge,
      active: routeIs('manage.overview'),
    },
    {
      label: _('Lottery'),
      href: route('lottery.show'),
      icon: IconDice5,
      active: routeIs('lottery.*'),
    },
    {
      label: _('Unit types'),
      href: route('manage.unit_types.index'),
      icon: IconStack2,
      active: routeIs('manage.unit_types.*'),
    },
    {
      label: _('Units'),
      href: route('manage.units.index'),
      icon: IconLayoutGrid,
      active: routeIs('manage.units.*'),
    },
    {
      label: _('Plan'),
      href: route('manage.plan.edit'),
      icon: IconMap,
      active: routeIs('manage.plan.*'),
    },
    {
      label: _('Families'),
      href: route('families.index'),
      icon: IconUsersGroup,
      active: routeIs('families.*'),
    },
    {
      label: _('People'),
      href: route('people.index'),
      icon: IconUsers,
      active: routeIs('people.*'),
    },
    {
      label: _('Invitations'),
      href: route('manage.invitations.index'),
      icon: IconMail,
      active: routeIs('manage.invitations.*'),
    },
    {
      label: _('Events'),
      href: route('events.index'),
      icon: IconCalendarEvent,
      active: routeIs('events.*'),
    },
    {
      label: _('Settings'),
      href: route('manage.settings.edit'),
      icon: IconSettings,
      active: routeIs('manage.settings.*'),
    },
  ];

  const banner = project.is_approved ? null : (
    <div className="shell-banner" role="status">
      <IconAlertTriangle size={20} />
      <p>
        {project.approval_requested_at
          ? _('Approval requested: limits apply until a superadmin approves the project.')
          : _('This project is awaiting approval: some limits apply.')}
      </p>
      <Link href={route('manage.overview')}>{_('Details')}</Link>
    </div>
  );

  return (
    <AppShell
      heading={_('Managing {project}', { project: project.name })}
      nav={[{ label: _('Managing {project}', { project: project.name }), items: nav }]}
      switchTo={{
        label: _('Back to the project'),
        href: route('projects.show'),
        icon: IconArrowLeft,
      }}
      banner={banner}
    >
      {children}
    </AppShell>
  );
}

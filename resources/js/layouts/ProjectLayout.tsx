import { AppShell, type NavEntry, type NavGroup } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';
import {
  IconAlertTriangle,
  IconCalendarEvent,
  IconDice5,
  IconGauge,
  IconHome,
  IconLayoutGrid,
  IconMail,
  IconMap,
  IconPhoto,
  IconSettings,
  IconStack2,
  IconUserPlus,
  IconUsers,
  IconUsersGroup,
} from '@tabler/icons-react';
import type { ReactNode } from 'react';

/**
 * A project's places, the lottery first after home: the shell's main group, here and on
 * the account's pages (which pass the project, not being inside it).
 */
export function projectPlaces(project: { id: number; family?: unknown }): NavEntry[] {
  const at = { project: project.id };

  return [
    {
      label: _('Home'),
      href: route('projects.show', at),
      icon: IconHome,
      active: routeIs('projects.show'),
    },
    {
      label: _('Lottery'),
      href: route('lottery.show', at),
      icon: IconDice5,
      active: routeIs('lottery.*'),
    },
    {
      label: _('Families'),
      href: route('families.index', at),
      icon: IconUsersGroup,
      active: routeIs('families.*'),
    },
    {
      label: _('People'),
      href: route('people.index', at),
      icon: IconUsers,
      active: routeIs('people.*'),
    },
    {
      label: _('Units'),
      href: route('units.index', at),
      icon: IconLayoutGrid,
      active: routeIs('units.*'),
    },
    {
      label: _('Events'),
      href: route('events.index', at),
      icon: IconCalendarEvent,
      active: routeIs('events.*'),
    },
    {
      label: _('Media'),
      href: route('media.index', at),
      icon: IconPhoto,
      active: routeIs('media.*'),
    },
    ...(project.family
      ? [
          {
            label: _('Invite a family member'),
            href: route('invitations.index', at),
            icon: IconUserPlus,
            active: routeIs('invitations.*'),
            action: true,
          },
        ]
      : []),
  ];
}

/** What only admins have a page for: setting the project up and its settings */
export function adminPlaces(project: { id: number }): NavEntry[] {
  const at = { project: project.id };

  return [
    {
      label: _('Setup'),
      href: route('manage.overview', at),
      icon: IconGauge,
      active: routeIs('manage.overview'),
    },
    {
      label: _('Unit types'),
      href: route('manage.unit_types.index', at),
      icon: IconStack2,
      active: routeIs('manage.unit_types.*'),
    },
    {
      label: _('Edit the units'),
      href: route('manage.units.index', at),
      icon: IconLayoutGrid,
      active: routeIs('manage.units.*'),
    },
    {
      label: _('Edit the plan'),
      href: route('manage.plan.edit', at),
      icon: IconMap,
      active: routeIs('manage.plan.*'),
    },
    {
      label: _('Invitations'),
      href: route('manage.invitations.index', at),
      icon: IconMail,
      active: routeIs('manage.invitations.*'),
    },
    {
      label: _('Settings'),
      href: route('manage.settings.edit', at),
      icon: IconSettings,
      active: routeIs('manage.settings.*'),
    },
  ];
}

/** The sidebar of a project: its places, and for admins what only they have pages for */
export function projectNav(
  project: { id: number; name: string; family?: unknown },
  admin: boolean,
): NavGroup[] {
  return [
    { label: project.name, items: projectPlaces(project) },
    ...(admin ? [{ label: _('Administration'), items: adminPlaces(project) }] : []),
  ];
}

/** A project's pages, the same for everyone; admins find their tools on them */
export function ProjectLayout({ children }: { children: ReactNode }) {
  const project = useCurrentPage().props.project;
  // Leaving a project that is gone (deleted, or left): the next page brings its own frame
  if (!project) return children;
  const places = projectPlaces(project);

  const banner =
    project.can.manage && !project.is_approved ? (
      <div className="shell-banner" role="status">
        <IconAlertTriangle size={20} />
        <p>
          {project.approval_requested_at
            ? _('Approval requested: limits apply until a superadmin approves the project.')
            : _('This project is awaiting approval: some limits apply.')}
        </p>
        <Link href={route('manage.overview')}>{_('Details')}</Link>
      </div>
    ) : null;

  return (
    <AppShell
      heading={project.name}
      nav={projectNav(project, project.can.manage)}
      tabs={places.filter((item) => !item.action).slice(0, 4)}
      banner={banner}
    >
      {children}
    </AppShell>
  );
}

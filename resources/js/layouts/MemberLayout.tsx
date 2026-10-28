import { AppShell, type NavEntry } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import {
  IconAdjustments,
  IconCalendarEvent,
  IconDice5,
  IconHome,
  IconLayoutGrid,
  IconPhoto,
  IconUserPlus,
  IconUsers,
  IconUsersGroup,
} from '@tabler/icons-react';
import type { ReactNode } from 'react';

/**
 * A project's places in the member app, the lottery first after home: the shell's main
 * group, here and on the account's pages (which pass the project, not being inside it).
 */
export function memberPlaces(project: { id: number; family?: unknown }): NavEntry[] {
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

/** The member app: what people do in a project */
export function MemberLayout({ children }: { children: ReactNode }) {
  const project = useCurrentPage().props.project!;
  const places = memberPlaces(project);

  return (
    <AppShell
      heading={project.name}
      nav={[{ label: project.name, items: places }]}
      tabs={places.filter((item) => !item.action).slice(0, 4)}
      switchTo={
        project.can.manage
          ? { label: _('Manage project'), href: route('manage.overview'), icon: IconAdjustments }
          : null
      }
    >
      {children}
    </AppShell>
  );
}

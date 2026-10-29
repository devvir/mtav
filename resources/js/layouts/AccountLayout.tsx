import { AppShell, type NavGroup } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import { IconBell, IconFolders, IconUser } from '@tabler/icons-react';
import type { ReactNode } from 'react';
import { projectNav, projectPlaces } from './ProjectLayout';

/**
 * Pages about the person rather than a project: notifications, profile. They stay in the
 * frame of the project last browsed (going to one's profile is not leaving it), or of the
 * only one; someone in several also finds all their projects here.
 */
export function AccountLayout({ children }: { children: ReactNode }) {
  const { projects, lastProject } = useCurrentPage().props;
  const only = projects.length === 1 ? projects[0]! : null;
  const within = projects.find((project) => project.id === lastProject) ?? only;

  const nav: NavGroup[] = [
    ...(within ? projectNav(within, within.is_admin) : []),
    {
      label: _('Your account'),
      items: [
        ...(only
          ? []
          : [
              {
                label: _('Your projects'),
                href: route('projects.index'),
                icon: IconFolders,
                active: routeIs('projects.index', 'projects.create'),
              },
            ]),
        {
          label: _('Notifications'),
          href: route('notifications.index'),
          icon: IconBell,
          active: routeIs('notifications.*'),
        },
        {
          label: _('Profile'),
          href: route('profile.edit'),
          icon: IconUser,
          active: routeIs('profile.*'),
        },
      ],
    },
  ];

  return (
    <AppShell
      heading={_('Your account')}
      nav={nav}
      tabs={
        within
          ? projectPlaces(within)
              .filter((item) => !item.action)
              .slice(0, 4)
          : undefined
      }
    >
      {children}
    </AppShell>
  );
}

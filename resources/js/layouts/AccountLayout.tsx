import { AppShell, type NavGroup } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import { IconBell, IconFolders, IconUser } from '@tabler/icons-react';
import type { ReactNode } from 'react';
import { memberPlaces } from './MemberLayout';

/**
 * Pages about the person rather than a project: notifications, profile. Someone in a single
 * project keeps its places above (they never choose among projects); someone in several
 * gets their projects instead.
 */
export function AccountLayout({ children }: { children: ReactNode }) {
  const { projects } = useCurrentPage().props;
  const only = projects.length === 1 ? projects[0]! : null;

  const nav: NavGroup[] = [
    ...(only ? [{ label: only.name, items: memberPlaces(only) }] : []),
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
    <AppShell heading={_('Your account')} nav={nav}>
      {children}
    </AppShell>
  );
}

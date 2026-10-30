import { AppShell } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import {
  IconAdjustments,
  IconBug,
  IconFlask,
  IconFolders,
  IconHistory,
  IconRocket,
  IconShieldCheck,
  IconUser,
} from '@tabler/icons-react';
import type { ReactNode } from 'react';

/** The platform console: superadmins only */
export function PlatformLayout({ children }: { children: ReactNode }) {
  const { props } = useCurrentPage();
  const canDeploy = Boolean(props.auth.user?.can_deploy);

  return (
    <AppShell
      heading={_('Platform console')}
      nav={[
        {
          label: _('Platform console'),
          items: [
            {
              label: _('Projects'),
              href: route('admin.projects.index'),
              icon: IconFolders,
              active: routeIs('admin.projects.*'),
            },
            {
              label: _('Accounts'),
              href: route('admin.users.index'),
              icon: IconUser,
              active: routeIs('admin.users.*'),
            },
            {
              label: _('Superadmins'),
              href: route('admin.superadmins.index'),
              icon: IconShieldCheck,
              active: routeIs('admin.superadmins.*'),
            },
            {
              label: _('Activity'),
              href: route('admin.activity.index'),
              icon: IconHistory,
              active: routeIs('admin.activity.*'),
            },
            {
              label: _('Errors'),
              href: route('admin.errors.index'),
              icon: IconBug,
              active: routeIs('admin.errors.*'),
            },
            {
              label: _('Settings'),
              href: route('admin.settings.edit'),
              icon: IconAdjustments,
              active: routeIs('admin.settings.*'),
            },
            ...(canDeploy
              ? [
                  {
                    label: _('Deployments'),
                    href: route('admin.deployments.index'),
                    icon: IconRocket,
                    active: routeIs('admin.deployments.*'),
                  },
                ]
              : []),
            {
              label: _('UI Lab'),
              href: route('admin.lab'),
              icon: IconFlask,
              active: routeIs('admin.lab'),
            },
          ],
        },
      ]}
    >
      {children}
    </AppShell>
  );
}

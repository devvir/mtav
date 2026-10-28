import { AppShell } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import { IconFlask, IconFolders, IconUser } from '@tabler/icons-react';
import type { ReactNode } from 'react';

/** The platform console: superadmins only */
export function PlatformLayout({ children }: { children: ReactNode }) {
  useCurrentPage();

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

import { TourHost } from '@/components/help/TourHost';
import { DialogHost } from '@/components/overlays/DialogHost';
import { Logo, NavItem, PersonAvatar } from '@/design';
import { useLiveNotifications } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, router } from '@inertiajs/react';
import { Burger, Drawer, Menu, Text, UnstyledButton } from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import {
  IconBook,
  IconChevronDown,
  IconFolders,
  IconLogout,
  IconMessageQuestion,
  IconShieldCheck,
  IconUser,
  type Icon,
} from '@tabler/icons-react';
import { forwardRef, useEffect, type ComponentProps, type ReactNode } from 'react';
import { NotificationsMenu } from './NotificationsMenu';
import { QuickActions } from './QuickActions';
import { useCurrentPage, useFlashToasts } from './page';

export interface NavEntry {
  label: string;
  href: string;
  icon: Icon;
  active?: boolean;
  count?: number;
  /** A call to action rather than a place: set apart, tinted */
  action?: boolean;
}

/** A group of places, under its name */
export interface NavGroup {
  label: string;
  items: NavEntry[];
}

/** A link that loads its page as soon as the pointer rests on it, so the click is instant */
const PrefetchLink = forwardRef<HTMLAnchorElement, ComponentProps<typeof Link>>(
  function PrefetchLink(props, ref) {
    return <Link ref={ref} prefetch cacheFor="20s" {...props} />;
  },
);

/**
 * The frame of every signed-in page: a glass bar on top (project switcher, notifications,
 * the person's menu), the area's navigation in groups (a sidebar on large screens, a
 * drawer on small ones, plus bottom tabs on phones in the member app), and the page.
 */
export function AppShell({
  heading,
  nav,
  tabs,
  banner,
  children,
}: {
  /** What this area is about (the project's name, "Platform console", …) */
  heading: string;
  nav: NavGroup[];
  /** Phone bottom tabs: the member app's main places */
  tabs?: NavEntry[];
  banner?: ReactNode;
  children: ReactNode;
}) {
  const { props } = useCurrentPage();
  const user = props.auth.user!;
  const project = props.project;
  const [drawer, { open, close }] = useDisclosure(false);

  useLiveNotifications();
  useFlashToasts();
  useEffect(() => router.on('navigate', close), [close]);

  const entry = (item: NavEntry) => (
    <NavItem
      key={item.href}
      as={PrefetchLink}
      href={item.href}
      icon={item.icon}
      label={item.label}
      active={item.active}
      count={item.count}
      data-action={item.action || undefined}
    />
  );

  const navigation = (
    <>
      <nav className="shell-nav" aria-label={_('Navigation')}>
        {nav.map((group) => {
          const places = group.items.filter((item) => !item.action);
          const actions = group.items.filter((item) => item.action);
          return (
            <div key={group.label} className="shell-group">
              <Text className="ds-label shell-heading ds-truncate">{group.label}</Text>
              {places.map(entry)}
              {actions.length ? <div className="shell-actions">{actions.map(entry)}</div> : null}
            </div>
          );
        })}
      </nav>
    </>
  );

  return (
    <div className="shell">
      <aside className="shell-sidebar">
        <Link href={route('home')} className="shell-logo">
          <Logo name={props.name} institution={props.institution} />
        </Link>
        {navigation}
      </aside>

      <div className="shell-main">
        <header className="ds-bar shell-bar">
          <Burger opened={drawer} onClick={open} aria-label={_('Menu')} hiddenFrom="lg" size="sm" />

          {project && props.projects.length > 1 ? (
            <Menu position="bottom-start" width={280}>
              <Menu.Target>
                <UnstyledButton className="shell-switcher">
                  <span className="ds-truncate">{project.name}</span>
                  <IconChevronDown size={16} />
                </UnstyledButton>
              </Menu.Target>
              <Menu.Dropdown>
                <Menu.Label>{_('Your projects')}</Menu.Label>
                {props.projects.map((item) => (
                  <Menu.Item
                    key={item.id}
                    component={Link}
                    href={route('projects.show', { project: item.id })}
                    rightSection={
                      item.is_admin ? <IconShieldCheck size={16} aria-label={_('Admin')} /> : null
                    }
                  >
                    {item.name}
                  </Menu.Item>
                ))}
                <Menu.Divider />
                <Menu.Item
                  component={Link}
                  href={route('projects.index')}
                  leftSection={<IconFolders size={16} />}
                >
                  {_('All your projects')}
                </Menu.Item>
              </Menu.Dropdown>
            </Menu>
          ) : (
            <Text fw={650} className="ds-truncate">
              {project?.name ?? heading}
            </Text>
          )}

          <div className="shell-bar-end">
            <QuickActions />
            <NotificationsMenu />

            <Menu position="bottom-end" width={260}>
              <Menu.Target>
                <UnstyledButton aria-label={_('Your account')} className="shell-account">
                  <PersonAvatar name={user.name} src={user.avatar} size={36} />
                </UnstyledButton>
              </Menu.Target>
              <Menu.Dropdown>
                <Menu.Label style={{ textTransform: 'none', letterSpacing: 0 }}>
                  <Text fw={650} c="var(--mtav-text)" className="ds-truncate">
                    {user.name}
                  </Text>
                  <Text size="xs" className="ds-truncate">
                    {user.email}
                  </Text>
                </Menu.Label>
                <Menu.Divider />
                {props.projects.length !== 1 ? (
                  <Menu.Item
                    component={Link}
                    href={route('projects.index')}
                    leftSection={<IconFolders size={16} />}
                  >
                    {_('Your projects')}
                  </Menu.Item>
                ) : null}
                <Menu.Item
                  component={Link}
                  href={route('profile.edit')}
                  leftSection={<IconUser size={16} />}
                >
                  {_('Profile')}
                </Menu.Item>
                {user.is_superadmin ? (
                  <Menu.Item
                    component={Link}
                    href={route('admin.projects.index')}
                    leftSection={<IconShieldCheck size={16} />}
                  >
                    {_('Platform console')}
                  </Menu.Item>
                ) : null}
                <Menu.Divider />
                <Menu.Item
                  component={Link}
                  href={route('documentation.guide')}
                  leftSection={<IconBook size={16} />}
                >
                  {_('Guide')}
                </Menu.Item>
                <Menu.Item
                  component={Link}
                  href={route('documentation.faq')}
                  leftSection={<IconMessageQuestion size={16} />}
                >
                  {_('Questions and answers')}
                </Menu.Item>
                <Menu.Divider />
                <Menu.Item
                  component={Link}
                  href={route('logout')}
                  method="post"
                  as="button"
                  leftSection={<IconLogout size={16} />}
                >
                  {_('Sign out')}
                </Menu.Item>
              </Menu.Dropdown>
            </Menu>
          </div>
        </header>

        {banner}

        <main className="shell-page" data-with-tabs={tabs?.length ? true : undefined}>
          {children}
        </main>

        {tabs?.length ? (
          <nav className="ds-bar shell-tabs" aria-label={_('Main')}>
            {tabs.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                prefetch
                cacheFor="20s"
                className="shell-tab"
                data-active={item.active || undefined}
                aria-current={item.active ? 'page' : undefined}
              >
                <item.icon size={24} stroke={1.8} />
                {item.label}
              </Link>
            ))}
          </nav>
        ) : null}
      </div>

      <Drawer
        opened={drawer}
        onClose={close}
        size={300}
        title={heading}
        hiddenFrom="lg"
        closeButtonProps={{ 'aria-label': _('Close') }}
      >
        {navigation}
      </Drawer>

      <DialogHost />
      <TourHost />
    </div>
  );
}

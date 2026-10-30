import { TourHost } from '@/components/help/TourHost';
import { OfflineStatus } from '@/components/layout/OfflineStatus';
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
import {
  forwardRef,
  memo,
  useEffect,
  useSyncExternalStore,
  type ComponentProps,
  type ReactNode,
} from 'react';
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
 * Where a visit to another page is heading while it loads (its path), so the navigation
 * lights the place at once and the page shows it is on its way. Visits within a page
 * (details, filters) and sending forms are not trips. Only what shows it listens: the rest
 * of the frame stays put.
 */
let trip: string | null = null;
const travellers = new Set<() => void>();
const setTrip = (to: string | null) => {
  if (to === trip) return;
  trip = to;
  travellers.forEach((notify) => notify());
};
let tracking = false;

function useTrip(): string | null {
  return useSyncExternalStore(
    (notify) => {
      if (!tracking) {
        tracking = true;
        router.on('start', ({ detail: { visit } }) => {
          // A page loaded in the background (kept for later, offline) is no trip: nothing
          // arrives to end it, and the page would stay dimmed
          if (visit.prefetch) return;
          if (visit.method === 'get' && visit.url.pathname !== window.location.pathname)
            setTrip(visit.url.pathname);
        });
        router.on('navigate', () => setTrip(null));
        router.on('finish', ({ detail: { visit } }) => !visit.completed && setTrip(null));
      }
      travellers.add(notify);
      return () => travellers.delete(notify);
    },
    () => trip,
  );
}

/** Whether a place is lit: the one a trip heads to, or else the one we are at */
const lit = (item: NavEntry, items: NavEntry[], to: string | null) =>
  to && items.some((other) => other.href === to) ? item.href === to : item.active;

/** The area's places in groups; while a trip loads, its place is the lit one */
const Navigation = memo(function Navigation({ nav }: { nav: NavGroup[] }) {
  const to = useTrip();
  const items = nav.flatMap((group) => group.items);
  const entry = (item: NavEntry) => {
    const active = lit(item, items, to);
    return (
      <NavItem
        key={item.href}
        as={PrefetchLink}
        href={item.href}
        icon={item.icon}
        label={item.label}
        active={active}
        count={item.count}
        data-action={item.action || undefined}
      />
    );
  };

  return (
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
  );
});

/** The page, dimmed while on its way to another */
function Page({ tabs, children }: { tabs: boolean; children: ReactNode }) {
  const to = useTrip();

  return (
    <main
      className="shell-page"
      data-with-tabs={tabs || undefined}
      data-loading={to ? true : undefined}
    >
      {children}
    </main>
  );
}

/** Phone bottom tabs: the member app's main places */
const Tabs = memo(function Tabs({ tabs }: { tabs: NavEntry[] }) {
  const to = useTrip();

  return (
    <nav className="ds-bar shell-tabs" aria-label={_('Main')}>
      {tabs.map((item) => (
        <Link
          key={item.href}
          href={item.href}
          prefetch
          cacheFor="20s"
          className="shell-tab"
          data-active={lit(item, tabs, to) || undefined}
          aria-current={lit(item, tabs, to) ? 'page' : undefined}
        >
          <item.icon size={24} stroke={1.8} />
          {item.label}
        </Link>
      ))}
    </nav>
  );
});

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

  const navigation = <Navigation nav={nav} />;

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
            <OfflineStatus />
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

        <Page tabs={Boolean(tabs?.length)}>{children}</Page>

        {tabs?.length ? <Tabs tabs={tabs} /> : null}
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

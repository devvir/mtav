import { AppShell, type NavEntry, type NavGroup } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { keepPlaces } from '@/lib/offline';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { ProjectFeature, ProjectFeatures } from '@/types/models';
import {
  IconCalendarEvent,
  IconDice5,
  IconHome,
  IconLayoutGrid,
  IconMessageCircle,
  IconMessages,
  IconPhoto,
  IconSettings,
  IconUserPlus,
  IconUsersGroup,
} from '@tabler/icons-react';
import { useEffect, useMemo, type ReactNode } from 'react';

/**
 * A project's places, the lottery first after home: the shell's main group, here and on
 * the account's pages (which pass the project, not being inside it).
 */
export function projectPlaces(project: {
  id: number;
  family?: unknown;
  unread_messages?: number | null;
  can?: { chat?: boolean };
  /** What the project has turned off stays out (unknown: as if on) */
  features?: Partial<ProjectFeatures>;
}): NavEntry[] {
  const at = { project: project.id };
  const on = (feature: ProjectFeature) => project.features?.[feature] !== false;

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
    // Families and everyone in them: one place, two views of it
    {
      label: _('People'),
      href: route('families.index', at),
      icon: IconUsersGroup,
      active: routeIs('families.*', 'people.*'),
    },
    {
      label: _('Units'),
      href: route('units.index', at),
      icon: IconLayoutGrid,
      active: routeIs('units.*'),
    },
    ...(on('events')
      ? [
          {
            label: _('Events'),
            href: route('events.index', at),
            icon: IconCalendarEvent,
            active: routeIs('events.*'),
          },
        ]
      : []),
    ...(on('gallery') || on('files')
      ? [
          {
            label: _('Media'),
            href: route('media.index', at),
            icon: IconPhoto,
            active: routeIs('media.*'),
          },
        ]
      : []),
    ...(on('forum')
      ? [
          {
            label: _('Forum'),
            href: route('forum.index', at),
            icon: IconMessages,
            active: routeIs('forum.*'),
          },
        ]
      : []),
    // Only for those with a place in the project (a superadmin overseeing it has none). On
    // larger screens the chat has its button at the bottom corner instead (ChatLauncher)
    ...(project.can?.chat
      ? [
          {
            label: _('Messages'),
            href: route('chat.index', at),
            icon: IconMessageCircle,
            active: routeIs('chat.*'),
            phoneOnly: true,
            count: project.unread_messages ?? undefined,
          },
        ]
      : []),
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

/** What only admins have: the project's settings (setting it up, invitations, the project) */
export function adminPlaces(project: { id: number }): NavEntry[] {
  return [
    {
      label: _('Settings'),
      href: route('manage.overview', { project: project.id }),
      icon: IconSettings,
      active: routeIs('manage.*'),
    },
  ];
}

/** The sidebar of a project: its places, and for admins its settings, apart at the bottom */
export function projectNav(
  project: { id: number; name: string; family?: unknown; features?: Partial<ProjectFeatures> },
  admin: boolean,
): NavGroup[] {
  return [
    { label: project.name, items: projectPlaces(project) },
    ...(admin ? [{ label: _('Settings'), items: adminPlaces(project), apart: true }] : []),
  ];
}

/** Once the page has settled: the background loading never competes with it */
const KEEP_PLACES_AFTER_MS = 4000;

/** The places kept on the device: a project's every place but its actions */
const tabsOf = (id: number) => projectPlaces({ id }).filter((item) => !item.action);

/** A project's pages, the same for everyone; admins find their tools on them */
export function ProjectLayout({ children }: { children: ReactNode }) {
  const { props } = useCurrentPage();
  const project = props.project;
  const id = project?.id;
  const manages = Boolean(project?.can.manage);
  const hasFamily = Boolean(project?.family);
  const features = JSON.stringify(project?.features);
  // Built once per place visited: visits within a page (details, filters) reuse them
  const [nav, tabs] = useMemo(
    () =>
      project
        ? project.needs_family
          ? [[], []]
          : [
              projectNav(project, manages),
              projectPlaces(project)
                .filter((item) => !item.action)
                .slice(0, 4),
            ]
        : [[], []],
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [
      id,
      project?.name,
      manages,
      hasFamily,
      project?.needs_family,
      project?.can.chat,
      features,
      project?.unread_messages,
      props.ziggy.name,
      props.locale,
    ],
  );
  // Its main places, kept on the device in the background: there offline, even unvisited
  useEffect(() => {
    if (!id) return undefined;
    const later = window.setTimeout(
      () => keepPlaces(tabsOf(id).map((item) => item.href)),
      KEEP_PLACES_AFTER_MS,
    );
    return () => window.clearTimeout(later);
  }, [id]);

  // Leaving a project that is gone (deleted, or left): the next page brings its own frame
  if (!project) return children;

  return (
    <AppShell heading={project.name} nav={nav} tabs={tabs}>
      {children}
    </AppShell>
  );
}

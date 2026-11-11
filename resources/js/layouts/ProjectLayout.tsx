import { AppShell, type NavEntry, type NavGroup } from '@/components/layout/AppShell';
import { useCurrentPage } from '@/components/layout/page';
import { keepPlaces } from '@/lib/offline';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { ProjectFeature, ProjectFeatures } from '@/types/models';
import { Alert } from '@mantine/core';
import {
  IconCalendarEvent,
  IconDice5,
  IconHome,
  IconLayoutGrid,
  IconLifebuoy,
  IconMessageCircle,
  IconMessages,
  IconPhoto,
  IconSettings,
  IconUserPlus,
  IconUsersGroup,
} from '@tabler/icons-react';
import { useEffect, useEffectEvent, useMemo, type ReactNode } from 'react';

/**
 * A project's places, the lottery first after home: the shell's main group, here and on
 * the account's pages (which pass the project, not being inside it).
 */
export function projectPlaces(project: {
  id: number;
  family?: unknown;
  is_guest?: boolean;
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
            label:
              on('gallery') && on('files') ? _('Media') : on('files') ? _('Files') : _('Gallery'),
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
      : project.is_guest
        ? [
            {
              label: _('Join your family'),
              href: route('family.create', at),
              icon: IconUserPlus,
              active: routeIs('family.create'),
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

/** The places kept on the device: a project's every place it has on, but its actions */
export const keptPlaces = (id: number, features?: Partial<ProjectFeatures>) =>
  projectPlaces({ id, features })
    .filter((item) => !item.action)
    .map((item) => item.href);

/** The project as the page frames it, if any */
type FramedProject = ReturnType<typeof useCurrentPage>['props']['project'];

/** All a project's sidebar and tabs show, as plain values */
interface NavShown {
  project: Parameters<typeof projectNav>[0] & Parameters<typeof projectPlaces>[0];
  admin: boolean;
  /** What lights up is asked of the route as they are built */
  route: string | null;
}

/**
 * The sidebar and the phone's tabs of a project, built once per place visited: visits within
 * a page (details, filters) bring a new project object each time, but the same values, so
 * they are built from those values as one (the language is set once, as the app starts).
 */
function useProjectNav(project: FramedProject, route: string | null): [NavGroup[], NavEntry[]] {
  const shown = JSON.stringify(
    project && !project.needs_family
      ? ({
          project: {
            id: project.id,
            name: project.name,
            family: Boolean(project.family),
            is_guest: project.is_guest,
            unread_messages: project.unread_messages,
            can: { chat: project.can.chat },
            features: project.features,
          },
          admin: project.can.manage,
          route,
        } satisfies NavShown)
      : null,
  );

  return useMemo(() => {
    const what = JSON.parse(shown) as NavShown | null;
    if (!what) return [[], []];
    const tabs = projectPlaces(what.project).filter((item) => !item.action);
    return [projectNav(what.project, what.admin), tabs.slice(0, 4)];
  }, [shown]);
}

/** A project's pages, the same for everyone; admins find their tools on them */
export function ProjectLayout({ children }: { children: ReactNode }) {
  const { props } = useCurrentPage();
  const project = props.project;
  const id = project?.id;
  const [nav, tabs] = useProjectNav(project, props.ziggy.name);

  // Its main places, kept on the device in the background: there offline, even unvisited
  // (again should what it has on change)
  const features = JSON.stringify(project?.features);
  const keepThem = useEffectEvent(() => {
    if (id) keepPlaces(keptPlaces(id, project?.features));
  });
  useEffect(() => {
    if (!id) return undefined;
    const later = window.setTimeout(() => keepThem(), KEEP_PLACES_AFTER_MS);
    return () => window.clearTimeout(later);
  }, [id, features]);

  // Leaving a project that is gone (deleted, or left): the next page brings its own frame
  if (!project) return children;

  return (
    <AppShell heading={project.name} nav={nav} tabs={tabs}>
      {/* A support account sees and does what anyone here does: it should never pass for one of them */}
      {props.auth.user?.is_superadmin ? (
        <Alert
          className="support-account"
          color="grape"
          variant="light"
          mb="md"
          icon={<IconLifebuoy size={18} />}
        >
          {_(
            'You are in a support account. What you write here, the project’s people read: use it only to try things out.',
          )}
        </Alert>
      ) : null}
      {children}
    </AppShell>
  );
}

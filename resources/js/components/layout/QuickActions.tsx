import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, usePage } from '@inertiajs/react';
import { ActionIcon, Menu, Tooltip } from '@mantine/core';
import {
  IconCalendarPlus,
  IconHomePlus,
  IconMailPlus,
  IconPhotoPlus,
  IconPlus,
  IconUserPlus,
  IconUsersPlus,
  type Icon,
} from '@tabler/icons-react';

interface Action {
  label: string;
  href: string;
  icon: Icon;
}

/**
 * What the person may do in the project, a click away from any page: a shortcut, never the
 * only way (each action also lives on its own page).
 */
export function QuickActions() {
  const project = usePage().props.project;
  if (!project) return null;

  const at = { project: project.id };
  const actions: Action[] = [
    ...(project.can.upload
      ? [
          {
            label: _('Share photos or files'),
            href: route('media.create', at),
            icon: IconPhotoPlus,
          },
        ]
      : []),
    ...(project.family && project.can.invite
      ? [
          {
            label: _('Invite a family member'),
            href: route('invitations.index', at),
            icon: IconUserPlus,
          },
        ]
      : []),
    ...(project.can.manage
      ? [
          {
            label: _('Create an event'),
            href: route('events.index', { ...at, new: 1 }),
            icon: IconCalendarPlus,
          },
          {
            label: _('Add a family'),
            href: route('families.index', { ...at, new: 1 }),
            icon: IconUsersPlus,
          },
          {
            label: _('Add units'),
            href: route('units.index', { ...at, new_unit: 1 }),
            icon: IconHomePlus,
          },
          {
            label: _('Invite people'),
            href: route('manage.invitations.index', at),
            icon: IconMailPlus,
          },
        ]
      : []),
  ];
  if (!actions.length) return null;

  return (
    <Menu position="bottom-end" width={250}>
      <Menu.Target>
        <Tooltip label={_('Quick actions')}>
          <ActionIcon
            size="xl"
            radius="xl"
            variant="subtle"
            color="gray"
            aria-label={_('Quick actions')}
          >
            <IconPlus size={22} stroke={1.8} />
          </ActionIcon>
        </Tooltip>
      </Menu.Target>
      <Menu.Dropdown>
        <Menu.Label>{_('Quick actions')}</Menu.Label>
        {actions.map((action) => (
          <Menu.Item
            key={action.label}
            component={Link}
            href={action.href}
            leftSection={<action.icon size={16} />}
          >
            {action.label}
          </Menu.Item>
        ))}
      </Menu.Dropdown>
    </Menu>
  );
}

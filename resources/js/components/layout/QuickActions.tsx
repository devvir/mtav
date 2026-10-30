import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, usePage } from '@inertiajs/react';
import { Button, Menu } from '@mantine/core';
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
import { useState } from 'react';

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
  const [opened, setOpened] = useState(false);
  if (!project) return null;

  const at = { project: project.id };
  // Admins: in the order a project is set up; members: what they share
  const actions: Action[] = [
    ...(project.can.manage
      ? [
          {
            label: _('Invite a member'),
            href: route('manage.invitations.index', at),
            icon: IconMailPlus,
          },
          {
            label: _('Add a family'),
            href: route('families.index', { ...at, new: 1 }),
            icon: IconUsersPlus,
          },
          {
            label: _('Set up units'),
            href: route('units.index', at),
            icon: IconHomePlus,
          },
          {
            label: _('Create an event'),
            href: route('events.index', { ...at, new: 1 }),
            icon: IconCalendarPlus,
          },
        ]
      : project.family && project.can.invite
        ? [
            {
              label: _('Invite a family member'),
              href: route('invitations.index', at),
              icon: IconUserPlus,
            },
          ]
        : []),
    ...(project.can.upload
      ? [
          {
            label: _('Upload photos or files'),
            href: route('media.index', { ...at, share: 1 }),
            icon: IconPhotoPlus,
          },
        ]
      : []),
  ];
  if (!actions.length) return null;

  return (
    <Menu position="bottom-end" width={250} opened={opened} onChange={setOpened}>
      <Menu.Target>
        {/* Said in words where there is room; on phones, its sign alone */}
        <Button
          variant="subtle"
          color="gray"
          radius="xl"
          className="shell-quick"
          aria-label={_('Quick actions')}
          leftSection={<IconPlus size={18} stroke={1.8} />}
        >
          <span className="shell-quick-label">{_('Quick actions')}</span>
        </Button>
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

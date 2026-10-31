import { ChecklistCorner, isSetUp, type Setup } from '@/components/domain/SetupChecklist';
import { DateTime } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { SettingsTabs } from '@/components/layout/SettingsTabs';
import { Glyph } from '@/design';
import { appName } from '@/lib/app';
import { confirm } from '@/lib/dialogs';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import { Link, router, usePage } from '@inertiajs/react';
import { Button, Card, Collapse, Group, Progress, Stack, Text } from '@mantine/core';
import { IconGauge, IconLayoutGrid, IconUsers, IconUsersGroup } from '@tabler/icons-react';
import { useState } from 'react';

type Key = 'unit_types' | 'units' | 'families' | 'people' | 'invitations' | 'media';

interface Props {
  counts: Record<Key, number>;
  checklist: Setup;
  approval: {
    approved: boolean;
    requested_at: string | null;
    limits: Record<string, number> | null;
    usage: Record<string, number> | null;
  };
}

export default function Overview({ counts, checklist, approval }: Props) {
  const project = usePage().props.project!;

  useProjectChanges({
    unit_types: ['counts', 'checklist'],
    units: ['counts', 'checklist'],
    families: ['counts', 'checklist', 'approval'],
    people: ['counts', 'checklist', 'approval'],
    invitations: ['counts'],
    lottery_runs: ['checklist'],
  });

  const usage =
    approval.limits && approval.usage
      ? [
          { label: _('People'), used: approval.usage.members, limit: approval.limits.members },
          { label: _('Families'), used: approval.usage.families, limit: approval.limits.families },
          { label: _('Units'), used: approval.usage.units, limit: approval.limits.units },
          {
            label: _('File storage (MB)'),
            used: approval.usage.media_storage_mb,
            limit: approval.limits.media_storage_mb,
          },
        ]
      : [];

  const requestApproval = async () => {
    const ok = await confirm({
      title: _('Make the project unlimited?'),
      message: _('The {app} team looks at the project and lifts its limits.', {
        app: appName(),
      }),
      confirmLabel: _('Ask for unlimited'),
    });
    if (ok) router.post(route('manage.approval.request'), {}, { preserveScroll: true });
  };

  // Quick ways to the three things a project is made of, with how far each has come
  const quick = [
    {
      icon: IconUsersGroup,
      title: _('Families'),
      count: _n('{count} family', '{count} families', counts.families),
      action: _('Add a family'),
      href: route('families.index', { new: 1 }),
    },
    {
      icon: IconUsers,
      title: _('People'),
      count: counts.invitations
        ? _('{people} people, {invitations} invited', {
            people: counts.people,
            invitations: counts.invitations,
          })
        : _n('{count} person', '{count} people', counts.people),
      action: _('Invite a member'),
      href: route('manage.invitations.index'),
    },
    {
      icon: IconLayoutGrid,
      title: _('Units'),
      count: _('{units} in {types}', {
        units: _n('{count} unit', '{count} units', counts.units),
        types: _n('{count} type', '{count} types', counts.unit_types),
      }),
      action: _('Set up units'),
      href: route('units.edit'),
    },
  ];

  return (
    <>
      <PageHeader
        tour="manage.setup"
        title={_('Settings')}
        description={project.name}
        actions={
          isSetUp(checklist) ? null : (
            <ChecklistCorner setup={checklist} opened={!Object.values(checklist).some(Boolean)} />
          )
        }
        help={
          <InfoTip title={_('Setting up the project')} tour="manage.setup">
            {_(
              'Its families, people and units, a click away; and the checklist, from an empty project to its lottery.',
            )}
          </InfoTip>
        }
      />
      <SettingsTabs />

      <div className="setup-quick" data-tour="setup.quick">
        {quick.map((item) => (
          <Card
            key={item.href}
            component={Link}
            href={item.href}
            p="md"
            className="ds-interactive setup-quick-card"
          >
            <Glyph icon={item.icon} size={40} />
            <div style={{ minWidth: 0 }}>
              <Text fw={700}>{item.title}</Text>
              <Text className="ds-caption">{item.count}</Text>
              <Text size="sm" fw={650} c="var(--mtav-brand)" mt={4}>
                {item.action}
              </Text>
            </div>
          </Card>
        ))}
      </div>

      <div style={{ maxWidth: '21rem', marginLeft: 'auto' }}>
        <ProjectCapacity approval={approval} usage={usage} onAsk={requestApproval} />
      </div>
    </>
  );
}

/**
 * A limited project's room: quiet, folded behind a button in a corner saying "Limited"
 * (a fact, never a warning), unfolded the room used and asking for unlimited. An
 * unlimited project shows nothing: there is nothing to know
 */
function ProjectCapacity({
  approval,
  usage,
  onAsk,
}: {
  approval: Props['approval'];
  usage: { label: string; used: number; limit: number }[];
  onAsk: () => void;
}) {
  const [open, setOpen] = useState(false);

  if (approval.approved) return null;

  return (
    <Stack gap="xs" data-tour="setup.approval">
      <Collapse expanded={open} transitionDuration={220}>
        <Card p="lg" className="capacity-card">
          <Text size="sm" c="dimmed">
            {_('What the project has room for:')}
          </Text>
          <Stack gap="sm" mt="md">
            {usage.map((item) => (
              <div key={item.label}>
                <Group justify="space-between" fz="sm">
                  <span>{item.label}</span>
                  <span className="ds-num" style={{ color: 'var(--mtav-muted)' }}>
                    {item.used} / {item.limit}
                  </span>
                </Group>
                <Progress
                  value={Math.min(100, (item.used / item.limit) * 100)}
                  color={item.used / item.limit > 0.85 ? 'yellow' : undefined}
                  radius="xl"
                  size="sm"
                  mt={4}
                  aria-label={item.label}
                />
              </div>
            ))}
          </Stack>
          <Group justify="flex-end" mt="md">
            {approval.requested_at ? (
              <Text size="sm" c="dimmed">
                {_('Unlimited asked for')} <DateTime value={approval.requested_at} relative />.
              </Text>
            ) : (
              <Button variant="light" size="xs" onClick={onAsk}>
                {_('Ask for unlimited')}
              </Button>
            )}
          </Group>
        </Card>
      </Collapse>
      <Group justify="flex-end">
        <Button
          variant="subtle"
          color="gray"
          size="xs"
          aria-expanded={open}
          leftSection={<IconGauge size={14} />}
          onClick={() => setOpen(!open)}
        >
          {_('Limited')}
        </Button>
      </Group>
    </Stack>
  );
}

import { SetupChecklist, type Setup } from '@/components/domain/SetupChecklist';
import { DateTime } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Stat } from '@/design';
import { appName } from '@/lib/app';
import { confirm } from '@/lib/dialogs';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router, usePage } from '@inertiajs/react';
import {
  Badge,
  Button,
  Card,
  Collapse,
  Group,
  Progress,
  SimpleGrid,
  Stack,
  Text,
} from '@mantine/core';
import {
  IconGauge,
  IconLayoutGrid,
  IconMail,
  IconPhoto,
  IconShieldCheck,
  IconStack2,
  IconUsers,
  IconUsersGroup,
} from '@tabler/icons-react';
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

  const tiles: {
    key: Key;
    label: string;
    icon: typeof IconStack2;
    tone: 'brand' | 'accent' | 'info' | 'positive' | 'warning' | 'neutral';
  }[] = [
    { key: 'unit_types', label: _('Unit types'), icon: IconStack2, tone: 'accent' },
    { key: 'units', label: _('Units'), icon: IconLayoutGrid, tone: 'brand' },
    { key: 'families', label: _('Families'), icon: IconUsersGroup, tone: 'brand' },
    { key: 'people', label: _('People'), icon: IconUsers, tone: 'info' },
    { key: 'invitations', label: _('Pending invitations'), icon: IconMail, tone: 'warning' },
    { key: 'media', label: _('Files'), icon: IconPhoto, tone: 'neutral' },
  ];

  return (
    <>
      <PageHeader
        tour="manage.setup"
        title={_('Overview')}
        description={project.name}
        help={
          <InfoTip title={_('Setting up the project')} tour="manage.setup">
            {_('The checklist takes you from an empty project to its lottery, one step at a time.')}
          </InfoTip>
        }
      />

      <SimpleGrid cols={{ base: 2, sm: 3, lg: 6 }} mb="lg">
        {tiles.map((tile) => (
          <Stat
            key={tile.key}
            label={tile.label}
            value={counts[tile.key]}
            icon={tile.icon}
            tone={tile.tone}
          />
        ))}
      </SimpleGrid>

      <div className="with-aside">
        <SetupChecklist setup={checklist} />

        <ProjectCapacity approval={approval} usage={usage} onAsk={requestApproval} />
      </div>
    </>
  );
}

/**
 * A limited project's room: quiet, folded behind a button in a corner saying "Limited"
 * (a fact, never a warning), unfolded the room used and asking for unlimited; an
 * unlimited project says so in a word
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

  if (approval.approved)
    return (
      <Group justify="flex-end" data-tour="setup.approval">
        <Badge variant="light" color="green" leftSection={<IconShieldCheck size={14} />}>
          {_('Unlimited')}
        </Badge>
      </Group>
    );

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

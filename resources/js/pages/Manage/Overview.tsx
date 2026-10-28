import { DateTime } from '@/components/feedback/DateTime';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Glyph, Panel, Stat } from '@/design';
import { confirm } from '@/lib/dialogs';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, router, usePage } from '@inertiajs/react';
import { Button, Card, Group, Progress, SimpleGrid, Stack, Text, Title } from '@mantine/core';
import {
  IconCircle,
  IconCircleCheckFilled,
  IconLayoutGrid,
  IconListCheck,
  IconMail,
  IconPhoto,
  IconShieldCheck,
  IconStack2,
  IconUsers,
  IconUsersGroup,
} from '@tabler/icons-react';

type Key = 'unit_types' | 'units' | 'families' | 'people' | 'invitations' | 'media';

interface Props {
  counts: Record<Key, number>;
  checklist: Record<
    'unit_types' | 'units' | 'families' | 'members' | 'balanced' | 'scheduled' | 'lottery',
    boolean
  >;
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

  const steps = [
    {
      done: checklist.unit_types,
      label: _('Add the unit types'),
      href: route('manage.unit_types.index'),
    },
    { done: checklist.units, label: _('Add the units'), href: route('manage.units.index') },
    {
      done: checklist.families,
      label: _('Register the families'),
      href: route('manage.families.index'),
    },
    {
      done: checklist.members,
      label: _('Invite their members'),
      href: route('manage.invitations.index'),
    },
    {
      done: checklist.balanced,
      label: _('Have as many units as families of each type'),
      href: route('manage.lottery.show'),
    },
    {
      done: checklist.scheduled,
      label: _('Schedule the lottery'),
      href: route('manage.lottery.show'),
    },
    { done: checklist.lottery, label: _('Run the lottery'), href: route('manage.lottery.show') },
  ];
  const done = steps.filter((step) => step.done).length;

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
      title: _('Ask for approval?'),
      message: _('A superadmin reviews the project; once approved, its limits are lifted.'),
      confirmLabel: _('Ask for approval'),
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
        <Panel
          title={_('Getting to the lottery')}
          icon={IconListCheck}
          aside={`${done} / ${steps.length}`}
          data-tour="setup.checklist"
        >
          <Progress
            value={(done / steps.length) * 100}
            radius="xl"
            size="sm"
            mb="md"
            color="green"
            aria-label={_('Getting to the lottery')}
          />
          <ol className="checklist">
            {steps.map((step) => (
              <li key={step.label}>
                <Link
                  href={step.href}
                  className="checklist-step"
                  data-done={step.done || undefined}
                >
                  {step.done ? (
                    <IconCircleCheckFilled size={22} className="text-positive" />
                  ) : (
                    <IconCircle size={22} />
                  )}
                  <span>{step.label}</span>
                </Link>
              </li>
            ))}
          </ol>
        </Panel>

        {!approval.approved ? (
          <Card p="lg" data-tour="setup.approval" className="approval-card">
            <Group gap="sm">
              <Glyph icon={IconShieldCheck} tone="warning" />
              <Title order={2} fz="md">
                {_('Awaiting approval')}
              </Title>
            </Group>
            <Text size="sm" c="dimmed" mt="sm">
              {_('Until a superadmin approves the project, it works within these limits:')}
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
            {approval.requested_at ? (
              <Text size="sm" mt="md">
                {_('Approval requested')} <DateTime value={approval.requested_at} relative />.
              </Text>
            ) : (
              <Button fullWidth mt="md" onClick={requestApproval}>
                {_('Ask for approval')}
              </Button>
            )}
          </Card>
        ) : null}
      </div>
    </>
  );
}

import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, Status, type Tone } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { router } from '@inertiajs/react';
import { Card, Group, Select, Text } from '@mantine/core';
import { IconHistory } from '@tabler/icons-react';

interface Entry {
  id: number;
  at: string;
  actor: string | null;
  action: string;
  subject_type: string | null;
  subject: string | null;
  project: string | null;
  changes: Record<string, [unknown, unknown] | unknown> | null;
  ip: string | null;
}

interface Props {
  activity: Paginated<Entry>;
  projects: { id: number; name: string }[];
  filters: { q: string | null; project: number | null; kind: 'changes' | 'sign_ins' | null };
}

/** What each action is called, and its tone */
const ACTIONS: Record<string, [() => string, Tone]> = {
  created: [() => _('Created'), 'positive'],
  updated: [() => _('Changed'), 'info'],
  deleted: [() => _('Deleted'), 'negative'],
  signed_in: [() => _('Signed in'), 'neutral'],
  signed_out: [() => _('Signed out'), 'neutral'],
  sign_in_failed: [() => _('Failed sign-in'), 'warning'],
  password_reset: [() => _('Password reset'), 'warning'],
};

/** What each kind of thing is called */
const SUBJECTS: Record<string, () => string> = {
  project: () => _('Project'),
  family: () => _('Family'),
  unit_type: () => _('Unit type'),
  unit: () => _('Unit'),
  membership: () => _('Membership'),
  invitation: () => _('Invitation'),
  join_request: () => _('Request to join'),
  event: () => _('Event'),
  media: () => _('File'),
  lottery_run: () => _('Lottery run'),
  platform_setting: () => _('Platform setting'),
  user: () => _('Account'),
  topic: () => _('Forum topic'),
  post: () => _('Forum post'),
};

const shown = (value: unknown) =>
  value === null || value === undefined || value === ''
    ? '—'
    : typeof value === 'object'
      ? JSON.stringify(value)
      : String(value);

/** A change: each field, from what to what (a person's own fields: only that they changed) */
function Changes({ changes }: { changes: Entry['changes'] }) {
  if (!changes) return null;
  return (
    <Text className="ds-caption activity-changes">
      {Object.entries(changes)
        .map(([field, change]) =>
          Array.isArray(change) && change.length === 2
            ? `${field}: ${shown(change[0])} → ${shown(change[1])}`
            : change === null
              ? field
              : `${field}: ${shown(change)}`,
        )
        .join(' · ')}
    </Text>
  );
}

/**
 * Everything that changed on the platform and every way in, newest first, for its
 * superadmins: to make it all auditable, never to watch what people read
 */
export default function Activity({ activity, projects, filters }: Props) {
  const filter = (next: Partial<Props['filters']>) =>
    router.get(
      route('admin.activity.index'),
      Object.fromEntries(Object.entries({ ...filters, ...next }).filter(([, value]) => value)),
      { preserveState: true, preserveScroll: true },
    );

  return (
    <>
      <PageHeader
        title={_('Activity')}
        description={_('Every change, and every way in, newest first.')}
        tour="platform.activity"
        help={
          <InfoTip title={_('Activity')} tour="platform.activity">
            {_(
              'What changed, who changed it and when, on every project; and who signed in. Never what people read: only what they did.',
            )}
          </InfoTip>
        }
      />
      <Group mb="md" gap="sm" align="flex-end" data-tour="activity.filters">
        <QuerySearch value={filters.q} placeholder={_('Search by person or name')} maw={320} />
        <Select
          aria-label={_('Project')}
          placeholder={_('Every project')}
          data={projects.map((project) => ({ value: String(project.id), label: project.name }))}
          value={filters.project ? String(filters.project) : null}
          onChange={(project) => filter({ project: project ? Number(project) : null })}
          searchable
          clearable
          maw={260}
        />
        <Select
          aria-label={_('Kind')}
          placeholder={_('Everything')}
          data={[
            { value: 'changes', label: _('Changes') },
            { value: 'sign_ins', label: _('Sign-ins') },
          ]}
          value={filters.kind}
          onChange={(kind) => filter({ kind: kind as Props['filters']['kind'] })}
          clearable
          maw={200}
        />
      </Group>
      {activity.data.length ? (
        <Card p={0} data-tour="activity.list">
          {activity.data.map((entry) => {
            const [label, tone] = ACTIONS[entry.action] ?? [() => entry.action, 'neutral' as Tone];
            return (
              <div key={entry.id} className="row activity-row">
                <Group gap="sm" wrap="wrap">
                  <Status tone={tone} pill>
                    {label()}
                  </Status>
                  {entry.subject_type ? (
                    <Text fw={650}>
                      {(SUBJECTS[entry.subject_type] ?? (() => entry.subject_type!))()}
                      {entry.subject ? ` «${entry.subject}»` : null}
                    </Text>
                  ) : null}
                  {entry.project ? <Text className="ds-caption">· {entry.project}</Text> : null}
                </Group>
                <Changes changes={entry.changes} />
                <Text className="ds-caption">
                  <DateTime value={entry.at} /> · {entry.actor ?? _('Nobody signed in')}
                  {entry.ip ? ` · ${entry.ip}` : null}
                </Text>
              </div>
            );
          })}
        </Card>
      ) : (
        <EmptyState icon={IconHistory} title={_('Nothing yet')} />
      )}
      <Pagination page={activity} />
    </>
  );
}

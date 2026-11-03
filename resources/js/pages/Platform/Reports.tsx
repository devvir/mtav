import { DateTime } from '@/components/feedback/DateTime';
import { Pagination } from '@/components/feedback/Pagination';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { _, _n } from '@/lib/translations';
import type { Paginated } from '@/types/models';
import { Anchor, Badge, Card, Group, Stack, Text } from '@mantine/core';
import { IconMessageReport } from '@tabler/icons-react';

interface Report {
  id: number;
  body: string;
  project: string | null;
  at: string;
}

interface Person {
  id: number;
  name: string;
  email: string;
  reports: Report[];
}

/** One report: what was said, when, and from which project */
function ReportItem({ report }: { report: Report }) {
  return (
    <div className="report-item">
      <Text style={{ whiteSpace: 'pre-line', overflowWrap: 'anywhere' }}>{report.body}</Text>
      <Text className="ds-caption" mt={2}>
        <DateTime value={report.at} relative />
        {report.project ? ` · ${report.project}` : null}
      </Text>
    </div>
  );
}

/**
 * What people told the platform's team through the Assistant: something that does not
 * work, or an idea. By person, whoever wrote most lately first.
 */
export default function Reports({
  people,
  unsigned,
}: {
  people: Paginated<Person>;
  /** Of accounts since deleted */
  unsigned: Report[];
}) {
  return (
    <>
      <PageHeader
        title={_('Reports')}
        description={_('What people tell the team through the Assistant, by person.')}
        help={
          <InfoTip title={_('Reports')}>
            {_(
              'Problems and ideas people sent from their conversation with the Assistant, a few a day each. Reply by email if something needs an answer.',
            )}
          </InfoTip>
        }
      />
      {people.data.length || unsigned.length ? (
        <Stack gap="sm">
          {people.data.map((person) => (
            <Card key={person.id} p="md">
              <Group justify="space-between" wrap="nowrap" mb="xs">
                <div style={{ minWidth: 0 }}>
                  <Text component="h2" fw={700} m={0} className="ds-truncate">
                    {person.name}
                  </Text>
                  <Anchor href={`mailto:${person.email}`} size="sm">
                    {person.email}
                  </Anchor>
                </div>
                <Badge variant="light">
                  {_n('{count} report', '{count} reports', person.reports.length)}
                </Badge>
              </Group>
              <Stack gap="sm">
                {person.reports.map((report) => (
                  <ReportItem key={report.id} report={report} />
                ))}
              </Stack>
            </Card>
          ))}
          {unsigned.length ? (
            <Card p="md">
              <Text component="h2" fw={700} m={0} mb="xs">
                {_('From deleted accounts')}
              </Text>
              <Stack gap="sm">
                {unsigned.map((report) => (
                  <ReportItem key={report.id} report={report} />
                ))}
              </Stack>
            </Card>
          ) : null}
        </Stack>
      ) : (
        <EmptyState icon={IconMessageReport} title={_('No reports yet')}>
          {_('People can send them from their conversation with the Assistant.')}
        </EmptyState>
      )}
      <Pagination page={people} />
    </>
  );
}

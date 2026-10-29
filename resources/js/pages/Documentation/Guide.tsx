import { PageHeader } from '@/components/layout/PageHeader';
import { _ } from '@/lib/translations';
import { Card, Typography } from '@mantine/core';

/** The guide's HTML comes from resources/views/guides (static, written by us) */
export default function Guide({ content }: { content: string; userRole: string }) {
  return (
    <>
      <PageHeader title={_('Guide')} />
      <Card p="xl" maw={860} className="guide">
        <Typography>
          <div dangerouslySetInnerHTML={{ __html: content }} />
        </Typography>
      </Card>
    </>
  );
}

import { _ } from '@/lib/translations';
import { Head } from '@inertiajs/react';
import { Card, Typography } from '@mantine/core';

/**
 * The guide's HTML comes from resources/views/guides (static, written by us), and brings
 * the page's heading with it
 */
export default function Guide({ content }: { content: string }) {
  return (
    <>
      <Head title={_('Guide')} />
      <Card p="xl" maw={860} className="guide">
        <Typography>
          <div dangerouslySetInnerHTML={{ __html: content }} />
        </Typography>
      </Card>
    </>
  );
}

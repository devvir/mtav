import { useCurrentPage } from '@/components/layout/page';
import { Logo } from '@/design';
import { _ } from '@/lib/translations';
import { Head, Link } from '@inertiajs/react';
import { Typography } from '@mantine/core';
import type { ReactNode } from 'react';

/**
 * The member manual on its own page, for anyone: to share, or to read before joining. Only
 * the manual (its words come from resources/views/guides, written by us), and the way back
 * to the landing page.
 */
export default function Manual({ content }: { content: string }) {
  const { props } = useCurrentPage();

  return (
    <main className="manual guide">
      <Head title={_('Member guide')} />
      <Link href="/" className="manual-home" aria-label={_('MTAV home')}>
        <Logo name={props.name} institution={props.institution} size={36} />
      </Link>
      <Typography>
        <div dangerouslySetInnerHTML={{ __html: content }} />
      </Typography>
    </main>
  );
}

// Its own frame: nothing around the manual
Manual.layout = (page: ReactNode) => page;

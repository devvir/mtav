import { useCurrentPage } from '@/components/layout/page';
import { Logo } from '@/design';
import { _ } from '@/lib/translations';
import { Head } from '@inertiajs/react';
import { Stack, Text, Title } from '@mantine/core';
import type { ReactNode } from 'react';

/** A deployment that has no owner yet: closed, saying only that it is being set up */
export default function UnderConstruction() {
  const { props } = useCurrentPage();

  return (
    <main className="under-construction">
      <Head title={_('Under construction')} />
      <Stack align="center" ta="center" gap="md" maw={440}>
        <Logo name={props.name} institution={props.institution} size={44} />
        <Title order={1} fz={30} mt="lg">
          {_('Under construction')}
        </Title>
        <Text c="dimmed">{_('This site is being set up. Come back soon.')}</Text>
      </Stack>
    </main>
  );
}

// Its own frame: the page is the whole window
UnderConstruction.layout = (page: ReactNode) => page;

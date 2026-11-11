import { LanguagePicker } from '@/components/layout/LanguagePicker';
import { DialogHost } from '@/components/overlays/DialogHost';
import { Logo } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, Link, usePage } from '@inertiajs/react';
import { Anchor, Container, Group, Stack, Text } from '@mantine/core';
import type { ReactNode } from 'react';

/**
 * The frame of an open lottery's pages: nothing to sign in to, no menu. They are found by their
 * link, never by a search.
 */
export function OpenLayout({ children }: { children: ReactNode }) {
  const { props } = usePage();

  return (
    <Container size="sm" py="lg">
      <Head>
        <meta name="robots" content="noindex" />
      </Head>
      <Stack gap="xl">
        <Group justify="space-between">
          <Anchor component={Link} href={route('home')} underline="never" c="inherit">
            <Group gap="xs">
              <Logo />
              <Text fw={700}>{props.name as string}</Text>
            </Group>
          </Anchor>
          <Group gap="xs">
            <Anchor href={route('privacy')} size="sm" c="dimmed">
              {_('Privacy')}
            </Anchor>
            <LanguagePicker locales={props.languages as Record<string, string>} />
          </Group>
        </Group>
        {children}
      </Stack>
      {/* What the page asks before taking something away */}
      <DialogHost />
    </Container>
  );
}

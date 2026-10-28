import { AccountLayout } from '@/layouts/AccountLayout';
import { AuthLayout } from '@/layouts/AuthLayout';
import { MemberLayout } from '@/layouts/MemberLayout';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button, Stack, Text, Title } from '@mantine/core';
import type { ReactNode } from 'react';

const contentFor = (status: number) =>
  ({
    403: { title: _('Not allowed'), message: _('You do not have permission to see this page.') },
    404: {
      title: _('Page not found'),
      message: _('This page does not exist, or you do not have access to it.'),
    },
    503: {
      title: _('Back soon'),
      message: _('We are doing some maintenance. Please try again in a few minutes.'),
    },
  })[status] ?? {
    title: _('Something went wrong'),
    message: _('Please try again. If it keeps happening, let us know.'),
  };

export default function Error({ status }: { status: number }) {
  const content = contentFor(status);

  return (
    <>
      <Head title={content.title} />
      <Stack align="center" ta="center" py={64} maw={460} mx="auto" gap="xs">
        <Text className="error-status">{status}</Text>
        <Title order={1} fz={28}>
          {content.title}
        </Title>
        <Text c="dimmed">{content.message}</Text>
        <Button component={Link} href={route('home')} mt="lg">
          {_('Go to the start')}
        </Button>
      </Stack>
    </>
  );
}

/** Inside a project, its member app; otherwise the account area, or the sign-in card */
function ErrorLayout({ children }: { children: ReactNode }) {
  const { props } = usePage();
  const Layout = props.project ? MemberLayout : props.auth.user ? AccountLayout : AuthLayout;

  return <Layout>{children}</Layout>;
}

Error.layout = (page: ReactNode) => <ErrorLayout>{page}</ErrorLayout>;

import { AuthHeading } from '@/components/layout/AuthHeading';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, router, useForm } from '@inertiajs/react';
import { Button, Stack } from '@mantine/core';
import { IconMailCheck } from '@tabler/icons-react';

export default function VerifyEmail({ email }: { email: string }) {
  const form = useForm({});

  return (
    <>
      <Head title={_('Confirm your email')} />
      <AuthHeading icon={IconMailCheck} title={_('Confirm your email')}>
        {_('We sent a link to {email}. Open it to confirm your address and continue.', { email })}
      </AuthHeading>
      <Stack gap="sm">
        <Button loading={form.processing} onClick={() => form.post(route('verification.send'))}>
          {_('Send the link again')}
        </Button>
        <Button variant="subtle" onClick={() => router.post(route('logout'))}>
          {_('Sign out')}
        </Button>
      </Stack>
    </>
  );
}

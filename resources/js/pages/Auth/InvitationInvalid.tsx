import { AuthHeading } from '@/components/layout/AuthHeading';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@mantine/core';
import { IconMailX } from '@tabler/icons-react';

export default function InvitationInvalid() {
  return (
    <>
      <Head title={_('Invitation not valid')} />
      <AuthHeading icon={IconMailX} title={_('This invitation is no longer valid')}>
        {_(
          'It may have expired, been withdrawn, or been used already. Ask whoever invited you to send a new one.',
        )}
      </AuthHeading>
      <Button fullWidth component={Link} href={route('login')}>
        {_('Go to sign in')}
      </Button>
    </>
  );
}

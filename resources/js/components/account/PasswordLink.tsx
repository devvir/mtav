import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { Anchor } from '@mantine/core';

/** For a password forgotten or never chosen (an account opened with Google) */
export function PasswordLink() {
  return (
    <Anchor
      component="button"
      type="button"
      size="sm"
      onClick={() => router.post(route('password.link'), {}, { preserveScroll: true })}
    >
      {_('Email me a link to choose a password')}
    </Anchor>
  );
}

import { PasswordLink } from '@/components/account/PasswordLink';
import { Form } from '@/components/forms/Form';
import { AuthHeading } from '@/components/layout/AuthHeading';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, Link, useForm } from '@inertiajs/react';
import { Anchor, Button, Group, PasswordInput } from '@mantine/core';
import { IconLock } from '@tabler/icons-react';

/** The password once more, before something grave */
export default function ConfirmPassword() {
  const form = useForm({ password: '' });

  return (
    <>
      <Head title={_('Confirm your password')} />
      <AuthHeading icon={IconLock} title={_('Confirm your password')}>
        {_('This action asks for your password once more.')}
      </AuthHeading>
      <Form onSubmit={() => form.post(route('password.confirm'), { onFinish: () => form.reset() })}>
        <PasswordInput
          label={_('Password')}
          autoComplete="current-password"
          autoFocus
          {...field(form, 'password')}
        />
        <Button type="submit" fullWidth size="md" loading={form.processing}>
          {_('Confirm')}
        </Button>
      </Form>
      <Group justify="space-between" mt="xl">
        <PasswordLink />
        <Anchor component={Link} href={route('home')} size="sm" fw={600}>
          {_('Back')}
        </Anchor>
      </Group>
    </>
  );
}

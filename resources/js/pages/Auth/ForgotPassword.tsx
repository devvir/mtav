import { Form } from '@/components/forms/Form';
import { AuthHeading } from '@/components/layout/AuthHeading';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, Link, useForm } from '@inertiajs/react';
import { Anchor, Button, Text, TextInput } from '@mantine/core';
import { IconKey } from '@tabler/icons-react';

export default function ForgotPassword() {
  const form = useForm({ email: '' });

  return (
    <>
      <Head title={_('Forgot your password?')} />
      <AuthHeading icon={IconKey} title={_('Forgot your password?')}>
        {_('We will email you a link to choose a new one.')}
      </AuthHeading>
      <Form onSubmit={() => form.post(route('password.email'))}>
        <TextInput
          label={_('Email')}
          type="email"
          autoComplete="email"
          autoFocus
          {...field(form, 'email')}
        />
        <Button type="submit" fullWidth size="md" loading={form.processing}>
          {_('Send the link')}
        </Button>
      </Form>
      <Text ta="center" size="sm" mt="xl">
        <Anchor component={Link} href={route('login')} fw={600}>
          {_('Back to sign in')}
        </Anchor>
      </Text>
    </>
  );
}

import { Form } from '@/components/forms/Form';
import { AuthHeading } from '@/components/layout/AuthHeading';
import { checkbox, field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, Link, useForm } from '@inertiajs/react';
import { Anchor, Button, Checkbox, Group, PasswordInput, Text, TextInput } from '@mantine/core';

export default function Login() {
  const form = useForm({ email: '', password: '', remember: false });

  return (
    <>
      <Head title={_('Sign in')} />
      <AuthHeading title={_('Sign in')} />
      <Form onSubmit={() => form.post(route('login'), { onFinish: () => form.reset('password') })}>
        <TextInput
          label={_('Email')}
          type="email"
          autoComplete="email"
          autoFocus
          {...field(form, 'email')}
        />
        <PasswordInput
          label={_('Password')}
          autoComplete="current-password"
          {...field(form, 'password')}
        />
        <Group justify="space-between">
          <Checkbox label={_('Keep me signed in')} {...checkbox(form, 'remember')} />
          <Anchor component={Link} href={route('password.request')} size="sm" fw={600}>
            {_('Forgot your password?')}
          </Anchor>
        </Group>
        <Button type="submit" fullWidth size="md" loading={form.processing}>
          {_('Sign in')}
        </Button>
      </Form>
      <Text ta="center" size="sm" c="dimmed" mt="xl">
        {_('New here?')}{' '}
        <Anchor component={Link} href={route('register')} fw={600}>
          {_('Create an account')}
        </Anchor>
      </Text>
    </>
  );
}

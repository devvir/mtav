import { Form } from '@/components/forms/Form';
import { checkbox, field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, useForm } from '@inertiajs/react';
import { Anchor, Button, Checkbox, Group, PasswordInput, TextInput } from '@mantine/core';

/** Signing in with an email and password */
export function SignInForm() {
  const form = useForm({ email: '', password: '', remember: false });

  return (
    <Form onSubmit={() => form.post(route('login'), { onFinish: () => form.reset('password') })}>
      <TextInput
        label={_('Email')}
        type="email"
        autoComplete="email"
        data-autofocus
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
  );
}

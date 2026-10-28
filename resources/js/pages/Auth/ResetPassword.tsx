import { Form } from '@/components/forms/Form';
import { AuthHeading } from '@/components/layout/AuthHeading';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, useForm } from '@inertiajs/react';
import { Button, PasswordInput, TextInput } from '@mantine/core';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
  const form = useForm({ token, email, password: '', password_confirmation: '' });

  return (
    <>
      <Head title={_('Choose a new password')} />
      <AuthHeading title={_('Choose a new password')} />
      <Form
        onSubmit={() =>
          form.post(route('password.store'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
          })
        }
      >
        <TextInput
          label={_('Email')}
          type="email"
          autoComplete="email"
          readOnly
          {...field(form, 'email')}
        />
        <PasswordInput
          label={_('New password')}
          description={_('At least 8 characters')}
          autoComplete="new-password"
          autoFocus
          {...field(form, 'password')}
        />
        <PasswordInput
          label={_('Repeat the password')}
          autoComplete="new-password"
          {...field(form, 'password_confirmation')}
        />
        <Button type="submit" fullWidth size="md" loading={form.processing}>
          {_('Save the password')}
        </Button>
      </Form>
    </>
  );
}

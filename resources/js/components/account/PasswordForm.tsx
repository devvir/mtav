import { Form, FormActions } from '@/components/forms/Form';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { useForm } from '@inertiajs/react';
import { PasswordInput } from '@mantine/core';

/** Changing one's password, the current one first */
export function PasswordForm() {
  const form = useForm({ current_password: '', password: '', password_confirmation: '' });

  return (
    <Form
      onSubmit={() =>
        form.put(route('password.update'), { preserveScroll: true, onSuccess: () => form.reset() })
      }
    >
      <PasswordInput
        label={_('Current password')}
        withAsterisk
        autoComplete="current-password"
        {...field(form, 'current_password')}
      />
      <PasswordInput
        label={_('New password')}
        description={_('At least 8 characters')}
        withAsterisk
        autoComplete="new-password"
        {...field(form, 'password')}
      />
      <PasswordInput
        label={_('Repeat the new password')}
        withAsterisk
        autoComplete="new-password"
        {...field(form, 'password_confirmation')}
      />
      <FormActions submit={_('Change the password')} processing={form.processing} />
    </Form>
  );
}

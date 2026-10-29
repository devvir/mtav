import { Form, RequiredNote } from '@/components/forms/Form';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { useForm } from '@inertiajs/react';
import { Button, PasswordInput, SimpleGrid, TextInput } from '@mantine/core';

/** A new account: to start a project, or to take part in one */
export function SignUpForm() {
  const form = useForm({
    firstname: '',
    lastname: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  return (
    <Form
      onSubmit={() =>
        form.post(route('register'), {
          onFinish: () => form.reset('password', 'password_confirmation'),
        })
      }
    >
      <SimpleGrid cols={{ base: 1, sm: 2 }}>
        <TextInput
          label={_('First name')}
          withAsterisk
          autoComplete="given-name"
          data-autofocus
          {...field(form, 'firstname')}
        />
        <TextInput label={_('Last name')} autoComplete="family-name" {...field(form, 'lastname')} />
      </SimpleGrid>
      <TextInput
        label={_('Email')}
        withAsterisk
        type="email"
        autoComplete="email"
        {...field(form, 'email')}
      />
      <PasswordInput
        label={_('Password')}
        description={_('At least 8 characters')}
        withAsterisk
        autoComplete="new-password"
        {...field(form, 'password')}
      />
      <PasswordInput
        label={_('Repeat the password')}
        withAsterisk
        autoComplete="new-password"
        {...field(form, 'password_confirmation')}
      />
      <RequiredNote />
      <Button type="submit" fullWidth size="md" loading={form.processing}>
        {_('Create the account')}
      </Button>
    </Form>
  );
}

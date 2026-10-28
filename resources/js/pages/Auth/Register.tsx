import { Form, RequiredNote } from '@/components/forms/Form';
import { AuthHeading } from '@/components/layout/AuthHeading';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, Link, useForm } from '@inertiajs/react';
import { Anchor, Button, PasswordInput, SimpleGrid, Text, TextInput } from '@mantine/core';

export default function Register() {
  const form = useForm({
    firstname: '',
    lastname: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  return (
    <>
      <Head title={_('Create an account')} />
      <AuthHeading title={_('Create an account')}>
        {_('To start a project, or to take part in one you were invited to.')}
      </AuthHeading>
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
            autoFocus
            {...field(form, 'firstname')}
          />
          <TextInput
            label={_('Last name')}
            autoComplete="family-name"
            {...field(form, 'lastname')}
          />
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
      <Text ta="center" size="sm" c="dimmed" mt="xl">
        {_('Already have an account?')}{' '}
        <Anchor component={Link} href={route('login')} fw={600}>
          {_('Sign in')}
        </Anchor>
      </Text>
    </>
  );
}

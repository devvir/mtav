import { Form } from '@/components/forms/Form';
import { checkbox, field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, router, useForm } from '@inertiajs/react';
import {
  Anchor,
  Button,
  Checkbox,
  Divider,
  Group,
  PasswordInput,
  Select,
  TextInput,
} from '@mantine/core';
import { useState } from 'react';

export type DemoAccount = { id: number; name: string; email: string };

/** Signing in with an email and password; where demo accounts exist, as one of them too */
export function SignInForm({ demo }: { demo: DemoAccount[] | null }) {
  const form = useForm({ email: '', password: '', remember: false });

  return (
    <>
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
      {demo ? (
        <>
          <Divider label={_('or')} labelPosition="center" my="md" />
          <DemoSignIn accounts={demo} />
        </>
      ) : null}
    </>
  );
}

/** Any demo account, found by name or email, with no password */
function DemoSignIn({ accounts }: { accounts: DemoAccount[] }) {
  const [user, setUser] = useState<string | null>(null);
  const [entering, setEntering] = useState(false);

  return (
    <Form
      onSubmit={() =>
        router.post(
          route('login.demo'),
          { user },
          {
            onStart: () => setEntering(true),
            onFinish: () => setEntering(false),
          },
        )
      }
    >
      <Select
        label={_('Demo account')}
        data={accounts.map(({ id, name, email }) => ({
          value: String(id),
          label: `${name} (${email})`,
        }))}
        value={user}
        onChange={setUser}
        searchable
        withCheckIcon={false}
        nothingFoundMessage={_('No account matches')}
      />
      <Button
        type="submit"
        variant="default"
        fullWidth
        size="md"
        disabled={!user}
        loading={entering}
      >
        {_('Enter without a password')}
      </Button>
    </Form>
  );
}

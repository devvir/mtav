import { Form } from '@/components/forms/Form';
import { checkbox, field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Link, router, useForm } from '@inertiajs/react';
import {
  Anchor,
  Badge,
  Button,
  Checkbox,
  Group,
  Loader,
  PasswordInput,
  Select,
  Text,
  TextInput,
} from '@mantine/core';
import { useState } from 'react';

export type DemoRole = 'support' | 'admin' | 'member' | 'guest';
export type DemoAccount = { id: number; name: string; email: string; roles: DemoRole[] };

/** Signing in with an email and password */
export function SignInForm() {
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
    </>
  );
}

const ROLE_COLOURS: Record<DemoRole, string> = {
  support: 'grape',
  admin: 'blue',
  member: 'teal',
  guest: 'gray',
};

const roleLabel = (role: DemoRole) =>
  ({ support: _('Support'), admin: _('Admin'), member: _('Member'), guest: _('Guest') })[role];

/**
 * Where demo accounts exist (dev, staging, a developer's machine), the way in: any of them,
 * found by name, email or role, and entered as soon as it is picked, with no password
 */
export function DemoSignIn({ accounts }: { accounts: DemoAccount[] }) {
  const [entering, setEntering] = useState(false);
  const byId = new Map(accounts.map((account) => [String(account.id), account]));

  return (
    <Select
      label={_('Enter as a demo account')}
      placeholder={_('Choose an account: no password needed')}
      size="md"
      data={accounts.map(({ id, name }) => ({ value: String(id), label: name }))}
      searchable
      disabled={entering}
      rightSection={entering ? <Loader size="xs" /> : undefined}
      withCheckIcon={false}
      maxDropdownHeight={320}
      nothingFoundMessage={_('No account matches')}
      // By name, email or what they are ("admin"), whatever the accents
      filter={({ options, search }) => {
        const words = plain(search);
        return (options as { value: string; label: string }[]).filter((option) => {
          const account = byId.get(option.value)!;
          return plain(
            [account.name, account.email, ...account.roles.map(roleLabel)].join(' '),
          ).includes(words);
        });
      }}
      renderOption={({ option }) => {
        const account = byId.get(option.value)!;
        return (
          <Group gap="xs" wrap="nowrap" w="100%" title={account.email}>
            <Text size="sm" className="ds-truncate" style={{ flex: 1, minWidth: 0 }}>
              {account.name}
            </Text>
            {account.roles.map((role) => (
              <Badge key={role} size="sm" variant="light" color={ROLE_COLOURS[role]}>
                {roleLabel(role)}
              </Badge>
            ))}
          </Group>
        );
      }}
      onChange={(user) => {
        if (!user) return;
        router.post(
          route('login.demo'),
          { user },
          { onStart: () => setEntering(true), onFinish: () => setEntering(false) },
        );
      }}
    />
  );
}

/** Whatever the capitals and accents */
const plain = (text: string) =>
  text
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase()
    .trim();

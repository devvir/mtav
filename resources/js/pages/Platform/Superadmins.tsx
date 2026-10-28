import { Form } from '@/components/forms/Form';
import { PageHeader } from '@/components/layout/PageHeader';
import { Panel, PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router, useForm, usePage } from '@inertiajs/react';
import { Button, Card, Group, Text, TextInput } from '@mantine/core';
import { IconShieldCheck, IconShieldPlus, IconShieldX } from '@tabler/icons-react';

interface Superadmin {
  id: number;
  name: string;
  email: string;
}

/**
 * Who manages the whole platform. A rare decision, so it lives here, apart: never a button
 * offered on every account. Giving the role takes the account's exact email, twice.
 */
export default function Superadmins({ superadmins }: { superadmins: Superadmin[] }) {
  const me = usePage().props.auth.user?.id;
  const form = useForm({ email: '' });

  const grant = async () => {
    const ok = await confirm({
      title: _('Give the superadmin role?'),
      message: _(
        'Superadmins see and manage every project on the platform, approve them and delete them for good.',
      ),
      typeToConfirm: form.data.email,
      confirmLabel: _('Give the role'),
    });
    if (ok)
      form.post(route('admin.superadmins.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
      });
  };

  const revoke = async (user: Superadmin) => {
    const ok = await confirm({
      title: _("Remove {name}'s superadmin role?", { name: user.name }),
      confirmLabel: _('Remove the role'),
      danger: true,
    });
    if (ok)
      router.put(
        route('admin.users.superadmin', user.id),
        { grant: false },
        { preserveScroll: true },
      );
  };

  return (
    <>
      <PageHeader
        title={_('Superadmins')}
        description={_('Who manages the whole platform. A rare role, given with care.')}
      />
      <div className="with-aside">
        <Card p={0}>
          {superadmins.map((user) => (
            <Group key={user.id} className="row" gap="md" wrap="nowrap">
              <PersonAvatar name={user.name} size={40} />
              <div style={{ flex: 1, minWidth: 0 }}>
                <Text fw={700}>{user.name}</Text>
                <Text className="ds-caption ds-truncate">{user.email}</Text>
              </div>
              {user.id !== me ? (
                <Button
                  variant="default"
                  color="red"
                  c="red"
                  size="sm"
                  leftSection={<IconShieldX size={16} />}
                  onClick={() => revoke(user)}
                >
                  {_('Remove the role')}
                </Button>
              ) : (
                <IconShieldCheck size={20} aria-label={_('You')} />
              )}
            </Group>
          ))}
        </Card>
        <Panel title={_('Give the role to someone')} icon={IconShieldPlus} tone="warning">
          <Form onSubmit={grant}>
            <TextInput
              label={_('Their account’s email')}
              description={_('Exactly as they sign in')}
              withAsterisk
              type="email"
              autoComplete="off"
              {...field(form, 'email')}
            />
            <Group justify="flex-end">
              <Button type="submit" variant="default" loading={form.processing}>
                {_('Give the superadmin role')}
              </Button>
            </Group>
          </Form>
        </Panel>
      </div>
    </>
  );
}

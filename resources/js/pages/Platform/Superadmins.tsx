import { DateTime } from '@/components/feedback/DateTime';
import { Form } from '@/components/forms/Form';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Panel, PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _, _nodes } from '@/lib/translations';
import { router, useForm, usePage } from '@inertiajs/react';
import { ActionIcon, Button, Card, Group, Stack, Text, TextInput } from '@mantine/core';
import {
  IconClock,
  IconShieldCheck,
  IconShieldPlus,
  IconShieldX,
  IconX,
} from '@tabler/icons-react';

interface Superadmin {
  id: number;
  name: string;
  email: string;
  is_owner: boolean;
}

interface SupportInvitation {
  id: number;
  email: string;
  inviter: string | null;
  expires_at: string;
}

/**
 * Who manages the whole platform. A rare decision, so it lives here, apart: never a button
 * offered on every account. A support account is invited, on an address not in the app yet:
 * it is never anyone's own account, which stays as it is.
 */
export default function Superadmins({
  superadmins,
  invitations,
}: {
  superadmins: Superadmin[];
  invitations: SupportInvitation[];
}) {
  const me = usePage().props.auth.user?.id;
  const form = useForm({ email: '' });

  const grant = async () => {
    const ok = await confirm({
      title: _('Register a new superadmin?'),
      message: _(
        'Superadmins see and manage every project on the platform, approve them and delete them for good.',
      ),
      typeToConfirm: form.data.email,
      confirmLabel: _('Send invite'),
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

  // Sent and not answered yet: its link stops working
  const withdraw = async (invitation: SupportInvitation) => {
    const ok = await confirm({
      title: _('Withdraw the invitation to {email}?', { email: invitation.email }),
      confirmLabel: _('Withdraw'),
      danger: true,
    });
    if (ok)
      router.delete(route('admin.superadmins.invitations.destroy', invitation.id), {
        preserveScroll: true,
      });
  };

  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Superadmins')} tour="platform.superadmins">
            {_('Who manages the whole platform. A rare role, given with care.')}
          </InfoTip>
        }
        title={_('Superadmins')}
        tour="platform.superadmins"
        description={_('Who manages the whole platform. A rare role, given with care.')}
      />
      <div className="with-aside">
        <Card p={0} data-tour="superadmins.list">
          {superadmins.map((user) => (
            <Group key={user.id} className="row" gap="md" wrap="nowrap">
              <PersonAvatar name={user.name} size={40} />
              <div style={{ flex: 1, minWidth: 0 }}>
                <Text fw={700}>{user.name}</Text>
                <Text className="ds-caption ds-truncate">{user.email}</Text>
              </div>
              {user.id !== me && !user.is_owner ? (
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
                <IconShieldCheck size={20} aria-label={user.id === me ? _('You') : _('Owner')} />
              )}
            </Group>
          ))}
        </Card>
        <Stack gap="md">
          <Panel
            title={_('Register a new superadmin')}
            icon={IconShieldPlus}
            tone="warning"
            data-tour="superadmins.grant"
          >
            <Form onSubmit={grant}>
              <TextInput
                label={_('Email')}
                withAsterisk
                type="email"
                autoComplete="off"
                {...field(form, 'email')}
              />
              <Group justify="flex-end">
                <Button type="submit" variant="default" loading={form.processing}>
                  {_('Send invite')}
                </Button>
              </Group>
            </Form>
          </Panel>
          {invitations.length ? (
            <Panel
              title={_('Waiting for an answer ({count})', { count: invitations.length })}
              icon={IconClock}
              tone="warning"
              flush
            >
              {invitations.map((invitation) => (
                <Group key={invitation.id} className="row" wrap="nowrap">
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <Text fw={600} className="ds-truncate">
                      {invitation.email}
                    </Text>
                    <Text className="ds-caption ds-truncate">
                      {_nodes('Until {date}', {
                        date: <DateTime value={invitation.expires_at} time={false} />,
                      })}
                      {invitation.inviter
                        ? ` · ${_('by {name}', { name: invitation.inviter })}`
                        : null}
                    </Text>
                  </div>
                  <ActionIcon
                    aria-label={_('Withdraw the invitation to {email}', {
                      email: invitation.email,
                    })}
                    onClick={() => withdraw(invitation)}
                  >
                    <IconX size={18} />
                  </ActionIcon>
                </Group>
              ))}
            </Panel>
          ) : null}
        </Stack>
      </div>
    </>
  );
}

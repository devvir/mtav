import { DateTime } from '@/components/feedback/DateTime';
import { Form, RequiredNote } from '@/components/forms/Form';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { Panel } from '@/design';
import { confirm } from '@/lib/dialogs';
import { checkbox, field, nativeSelect } from '@/lib/forms';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Invitation, Ref } from '@/types/models';
import { router, useForm } from '@inertiajs/react';
import {
  ActionIcon,
  Badge,
  Button,
  Checkbox,
  Group,
  NativeSelect,
  SimpleGrid,
  Text,
  TextInput,
} from '@mantine/core';
import { IconClock, IconMailPlus, IconSend, IconX } from '@tabler/icons-react';

interface Props {
  invitations: Invitation[];
  families: Ref[];
  limits: { members: number; invitations_per_day: number } | null;
}

export default function ManageInvitations({ invitations, families, limits }: Props) {
  const preselected = new URLSearchParams(window.location.search).get('family');
  const form = useForm({
    email: '',
    firstname: '',
    lastname: '',
    family_id: preselected ?? (families[0] ? String(families[0].id) : ''),
    is_admin: false,
  });
  useProjectChanges({ invitations: ['invitations'], families: ['families'] });

  const send = () => {
    form.transform((data) => ({ ...data, family_id: data.family_id || null }));
    form.post(route('manage.invitations.store'), {
      preserveScroll: true,
      onSuccess: () => form.reset('email', 'firstname', 'lastname'),
    });
  };

  const withdraw = async (invitation: Invitation) => {
    const ok = await confirm({
      title: _('Withdraw the invitation to {email}?', { email: invitation.email }),
      confirmLabel: _('Withdraw'),
      danger: true,
    });
    if (ok)
      router.delete(route('manage.invitations.destroy', invitation.id), { preserveScroll: true });
  };

  return (
    <>
      <PageHeader
        title={_('Invitations')}
        description={_('People join the project from the link they receive by email.')}
        help={
          <InfoTip title={_('How invitations work')} tour="manage.invitations">
            {_(
              'People join only by invitation: an emailed link, valid for two weeks. Members can also invite people into their own family.',
            )}
          </InfoTip>
        }
      />
      <div className="two-columns">
        <Panel title={_('Invite someone')} icon={IconMailPlus} data-tour="invite.form">
          <Form onSubmit={send}>
            <TextInput
              data-tour="invite.email"
              label={_('Email')}
              withAsterisk
              type="email"
              autoComplete="off"
              {...field(form, 'email')}
            />
            <SimpleGrid cols={{ base: 1, sm: 2 }}>
              <TextInput label={_('First name')} autoComplete="off" {...field(form, 'firstname')} />
              <TextInput label={_('Last name')} autoComplete="off" {...field(form, 'lastname')} />
            </SimpleGrid>
            <NativeSelect
              data-tour="invite.family"
              label={_('Family')}
              withAsterisk={!form.data.is_admin}
              data={[
                ...(form.data.is_admin || !families.length
                  ? [{ value: '', label: families.length ? _('No family') : _('No families yet') }]
                  : []),
                ...families.map((family) => ({ value: String(family.id), label: family.name })),
              ]}
              {...nativeSelect(form, 'family_id')}
            />
            <Checkbox
              data-tour="invite.admin"
              label={_('As an admin')}
              description={_('To help configure the project and run the lottery.')}
              {...checkbox(form, 'is_admin')}
            />
            {limits ? (
              <Text className="ds-caption">
                {_(
                  'Until the project is approved: up to {members} people, {day} invitations a day.',
                  {
                    members: limits.members,
                    day: limits.invitations_per_day,
                  },
                )}
              </Text>
            ) : null}
            <Group justify="flex-end" className="form-actions">
              <RequiredNote />
              <Button type="submit" loading={form.processing} leftSection={<IconSend size={18} />}>
                {_('Send the invitation')}
              </Button>
            </Group>
          </Form>
        </Panel>

        <Panel
          title={_('Waiting for an answer ({count})', { count: invitations.length })}
          icon={IconClock}
          tone="warning"
          flush
          data-tour="invite.pending"
        >
          {invitations.length ? (
            invitations.map((invitation) => (
              <Group key={invitation.id} className="row" wrap="nowrap">
                <div style={{ flex: 1, minWidth: 0 }}>
                  <Group gap="xs">
                    <Text fw={600} className="ds-truncate">
                      {invitation.name ?? invitation.email}
                    </Text>
                    {invitation.is_admin ? <Badge color="blue">{_('Admin')}</Badge> : null}
                  </Group>
                  <Text className="ds-caption ds-truncate">
                    {invitation.family ? `${invitation.family.name} · ` : null}
                    {_('Until')} <DateTime value={invitation.expires_at} time={false} />
                    {invitation.inviter
                      ? ` · ${_('by {name}', { name: invitation.inviter })}`
                      : null}
                  </Text>
                </div>
                <ActionIcon aria-label={_('Withdraw')} onClick={() => withdraw(invitation)}>
                  <IconX size={18} />
                </ActionIcon>
              </Group>
            ))
          ) : (
            <Text c="dimmed" ta="center" py="lg">
              {_('No pending invitations.')}
            </Text>
          )}
        </Panel>
      </div>
    </>
  );
}

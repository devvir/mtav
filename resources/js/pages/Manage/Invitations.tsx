import { DateTime } from '@/components/feedback/DateTime';
import { Form, RequiredNote } from '@/components/forms/Form';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { SettingsTabs } from '@/components/layout/SettingsTabs';
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
import { IconClock, IconMailPlus, IconSend, IconUserPlus, IconX } from '@tabler/icons-react';

interface Props {
  invitations: Invitation[];
  requests: { id: number; name: string; email: string; created_at: string }[];
  families: Ref[];
  allowsGuests: boolean;
  limits: { members: number; invitations_per_day: number } | null;
}

export default function ManageInvitations({
  invitations,
  requests,
  families,
  allowsGuests,
  limits,
}: Props) {
  const preselected = new URLSearchParams(window.location.search).get('family');
  const form = useForm({
    email: '',
    firstname: '',
    lastname: '',
    family_id: preselected ?? (families[0] ? String(families[0].id) : ''),
    is_admin: false,
  });
  useProjectChanges({
    invitations: ['invitations'],
    join_requests: ['requests'],
    families: ['families'],
  });

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

  const decline = async (request: Props['requests'][number]) => {
    const ok = await confirm({
      title: _('Decline the request of {name}?', { name: request.name }),
      confirmLabel: _('Decline'),
      danger: true,
    });
    if (ok)
      router.delete(route('manage.join_requests.decline', request.id), { preserveScroll: true });
  };

  return (
    <>
      <PageHeader
        tour="manage.invitations"
        title={_('Settings')}
        description={_('People join the project from the link they receive by email.')}
        help={
          <InfoTip title={_('How invitations work')} tour="manage.invitations">
            {_(
              'Invite people with an emailed link, valid for two weeks; members can invite into their own family. Someone already in the project with no family enters at once. If the project is in the directory, those asking to join appear here too.',
            )}
          </InfoTip>
        }
      />
      <SettingsTabs />
      {requests.length ? (
        <Panel
          title={_('Asking to join ({count})', { count: requests.length })}
          icon={IconUserPlus}
          tone="warning"
          flush
          style={{ marginBottom: 24 }}
          data-tour="invite.requests"
        >
          {requests.map((request) => (
            <Group key={request.id} className="row" wrap="wrap">
              <div style={{ flex: 1, minWidth: 200 }}>
                <Text fw={600} className="ds-truncate">
                  {request.name}
                </Text>
                <Text className="ds-caption ds-truncate">
                  {request.email} · <DateTime value={request.created_at} time={false} />
                </Text>
              </div>
              <Group gap="xs">
                <Button variant="default" onClick={() => decline(request)}>
                  {_('Decline')}
                </Button>
                <Button
                  onClick={() =>
                    router.post(
                      route('manage.join_requests.accept', request.id),
                      {},
                      { preserveScroll: true },
                    )
                  }
                >
                  {_('Accept')}
                </Button>
              </Group>
            </Group>
          ))}
        </Panel>
      ) : null}
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
              withAsterisk={!form.data.is_admin && !allowsGuests}
              data={[
                ...(form.data.is_admin || allowsGuests || !families.length
                  ? [
                      {
                        value: '',
                        label: !families.length
                          ? _('No families yet')
                          : form.data.is_admin
                            ? _('No family')
                            : _('No family (guest)'),
                      },
                    ]
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
                {_('This project has room for {members} people, and {day} invitations a day.', {
                  members: limits.members,
                  day: limits.invitations_per_day,
                })}
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

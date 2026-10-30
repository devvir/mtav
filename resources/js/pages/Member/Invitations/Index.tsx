import { DateTime } from '@/components/feedback/DateTime';
import { Form, RequiredNote } from '@/components/forms/Form';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { confirm } from '@/lib/dialogs';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Invitation } from '@/types/models';
import { router, useForm } from '@inertiajs/react';
import { ActionIcon, Button, Card, Group, SimpleGrid, Text, TextInput, Title } from '@mantine/core';
import { IconSend, IconX } from '@tabler/icons-react';

/** Members bring the rest of their family in */
export default function FamilyInvitations({
  family,
  invitations,
}: {
  family: { id: number; name: string };
  invitations: Invitation[];
}) {
  const form = useForm({ email: '', firstname: '', lastname: '' });

  const withdraw = async (invitation: Invitation) => {
    const ok = await confirm({
      title: _('Withdraw the invitation to {email}?', { email: invitation.email }),
      confirmLabel: _('Withdraw'),
      danger: true,
    });
    if (ok) router.delete(route('invitations.destroy', invitation.id), { preserveScroll: true });
  };

  return (
    <>
      <PageHeader
        help={
          <InfoTip title={_('Inviting your family')} tour="member.invitations">
            {_(
              'Invite the rest of your family by email: they join with your family already chosen.',
            )}
          </InfoTip>
        }
        title={_('Invite to your family')}
        tour="member.invitations"
        description={_(
          'Invite the people of the {family} family to the project. They receive an email with a link.',
          { family: family.name },
        )}
      />
      <Card p="lg" maw={720} data-tour="family_invite.form">
        <Form
          onSubmit={() =>
            form.post(route('invitations.store'), {
              preserveScroll: true,
              onSuccess: () => form.reset(),
            })
          }
        >
          <TextInput
            label={_('Their email')}
            withAsterisk
            type="email"
            autoComplete="off"
            {...field(form, 'email')}
          />
          <SimpleGrid cols={{ base: 1, sm: 2 }}>
            <TextInput
              label={_('First name')}
              description={_('For the email greeting')}
              autoComplete="off"
              {...field(form, 'firstname')}
            />
            <TextInput label={_('Last name')} autoComplete="off" {...field(form, 'lastname')} />
          </SimpleGrid>
          <Group justify="flex-end" className="form-actions">
            <RequiredNote />
            <Button type="submit" loading={form.processing} leftSection={<IconSend size={18} />}>
              {_('Send the invitation')}
            </Button>
          </Group>
        </Form>
      </Card>
      {invitations.length ? (
        <section style={{ marginTop: 32, maxWidth: 720 }} data-tour="family_invite.pending">
          <Title order={2} fz="lg" mb="sm">
            {_('Waiting for an answer')}
          </Title>
          <Card p={0}>
            {invitations.map((invitation) => (
              <Group key={invitation.id} className="row" wrap="nowrap">
                <div style={{ flex: 1, minWidth: 0 }}>
                  <Text fw={600} className="ds-truncate">
                    {invitation.name ?? invitation.email}
                  </Text>
                  <Text className="ds-caption">
                    {invitation.name ? `${invitation.email} · ` : null}
                    {_('Until')} <DateTime value={invitation.expires_at} time={false} />
                  </Text>
                </div>
                {invitation.can.delete ? (
                  <ActionIcon aria-label={_('Withdraw')} onClick={() => withdraw(invitation)}>
                    <IconX size={18} />
                  </ActionIcon>
                ) : null}
              </Group>
            ))}
          </Card>
        </section>
      ) : null}
    </>
  );
}

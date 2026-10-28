import { Form, RequiredNote } from '@/components/forms/Form';
import { AuthHeading } from '@/components/layout/AuthHeading';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Alert, Button, PasswordInput, SimpleGrid, Text, TextInput } from '@mantine/core';
import { IconConfetti } from '@tabler/icons-react';

interface Props {
  token: string;
  invitation: {
    email: string;
    firstname: string | null;
    lastname: string | null;
    is_admin: boolean;
    project: string;
    family: string | null;
    inviter: string | null;
  };
  mismatch: boolean;
}

/**
 * Where an invitation link lands: join with a new account, join as the signed-in person,
 * or learn that the invitation is meant for someone else.
 */
export default function Invitation({ token, invitation, mismatch }: Props) {
  const user = usePage().props.auth.user;
  const form = useForm({
    firstname: invitation.firstname ?? '',
    lastname: invitation.lastname ?? '',
    password: '',
    password_confirmation: '',
  });
  const accept = () => form.post(route('invitation.accept', token));

  const { is_admin, family, project } = invitation;
  const what =
    is_admin && family
      ? _('to help manage {project}, as a member of the {family} family', { project, family })
      : is_admin
        ? _('to help manage {project}', { project })
        : _('to join the {family} family in {project}', { project, family: family ?? '' });

  return (
    <>
      <Head title={_('Invitation to {project}', { project })} />
      <AuthHeading icon={IconConfetti} title={_('You are invited')}>
        {invitation.inviter
          ? _('{inviter} invites you {what}.', { inviter: invitation.inviter, what })
          : _('You are invited {what}.', { what })}
      </AuthHeading>

      {mismatch ? (
        <>
          <Alert color="yellow" radius="lg">
            {_('This invitation is for {email}, but you are signed in as {current}.', {
              email: invitation.email,
              current: user?.email ?? '',
            })}
          </Alert>
          <Button fullWidth mt="lg" variant="default" onClick={() => router.post(route('logout'))}>
            {_('Sign out to accept it')}
          </Button>
        </>
      ) : user ? (
        <Button fullWidth size="md" loading={form.processing} onClick={accept}>
          {_('Join {project}', { project })}
        </Button>
      ) : (
        <Form onSubmit={accept}>
          <Text size="sm" c="dimmed">
            {_('Choose a password for your account ({email}).', { email: invitation.email })}
          </Text>
          <SimpleGrid cols={{ base: 1, sm: 2 }}>
            <TextInput
              label={_('First name')}
              withAsterisk
              autoComplete="given-name"
              {...field(form, 'firstname')}
            />
            <TextInput
              label={_('Last name')}
              autoComplete="family-name"
              {...field(form, 'lastname')}
            />
          </SimpleGrid>
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
            {_('Join {project}', { project })}
          </Button>
        </Form>
      )}
    </>
  );
}

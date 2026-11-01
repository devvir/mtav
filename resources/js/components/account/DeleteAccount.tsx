import { PasswordLink } from '@/components/account/PasswordLink';
import { Glyph } from '@/design';
import { prompt } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router, usePage } from '@inertiajs/react';
import { Button, Group, Text, Title } from '@mantine/core';
import { IconAlertTriangle, IconTrash } from '@tabler/icons-react';

/**
 * Deleting one's own account, at the end of the profile: said plainly what goes and what
 * stays, confirmed with one's password
 */
export function DeleteAccount() {
  const errors = usePage().props.errors as Record<string, string | undefined>;
  const error = errors.password ?? errors.confirmation;

  const destroy = async () => {
    const password = await prompt({
      title: _('Delete your account?'),
      message: _(
        'You leave every project, and your profile is gone. What you shared stays with the projects, without your name. This cannot be undone.',
      ),
      label: _('Your password'),
      password: true,
      confirmLabel: _('Delete my account'),
      danger: true,
    });
    if (password)
      router.delete(route('profile.destroy'), { data: { password }, preserveScroll: true });
  };

  return (
    <div className="danger-card ds-surface" style={{ marginTop: 32, padding: 20 }}>
      <Group gap="sm">
        <Glyph icon={IconAlertTriangle} tone="negative" />
        <Title order={2} fz="lg" c="var(--mtav-negative)">
          {_('Delete your account')}
        </Title>
      </Group>
      <Text c="dimmed" mt="sm">
        {_('You leave every project. What you shared stays with them, without your name.')}
      </Text>
      {error ? (
        <Text c="red" size="sm" mt="sm" role="alert">
          {error}
        </Text>
      ) : null}
      {errors.password ? (
        <div style={{ marginTop: 8 }}>
          <PasswordLink />
        </div>
      ) : null}
      <Button
        color="red"
        variant="light"
        mt="md"
        leftSection={<IconTrash size={18} />}
        onClick={destroy}
      >
        {_('Delete my account')}
      </Button>
    </div>
  );
}

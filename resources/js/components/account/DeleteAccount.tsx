import { Glyph } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { router, usePage } from '@inertiajs/react';
import { Button, Group, Text, Title } from '@mantine/core';
import { IconAlertTriangle, IconTrash } from '@tabler/icons-react';

/**
 * Deleting one's own account, at the end of the profile: said plainly what goes and what
 * stays, confirmed by typing one's email
 */
export function DeleteAccount({ email }: { email: string }) {
  const error = (usePage().props.errors as Record<string, string | undefined>).confirmation;

  const destroy = async () => {
    const ok = await confirm({
      title: _('Delete your account?'),
      message: _(
        'You leave every project, and your profile is gone. What you shared stays with the projects, without your name. This cannot be undone.',
      ),
      typeToConfirm: email,
      confirmLabel: _('Delete my account'),
      danger: true,
    });
    if (ok)
      router.delete(route('profile.destroy'), {
        data: { confirmation: email },
        preserveScroll: true,
      });
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

import { Form, RequiredNote } from '@/components/forms/Form';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Family } from '@/types/models';
import { useForm, usePage } from '@inertiajs/react';
import { Button, Group, SimpleGrid, TextInput } from '@mantine/core';
import { IconSend } from '@tabler/icons-react';

/**
 * Someone invited into one family, the one it is opened from: admins into any, members into
 * their own
 */
export function FamilyInvitationForm({ family, onDone }: { family: Family; onDone: () => void }) {
  const admin = usePage().props.project?.can.manage;
  const form = useForm({ email: '', firstname: '', lastname: '', family_id: family.id });

  return (
    <Form
      onSubmit={() =>
        form.post(route(admin ? 'manage.invitations.store' : 'invitations.store'), {
          preserveScroll: true,
          preserveState: true,
          onSuccess: onDone,
        })
      }
    >
      <TextInput label={_('Family')} value={family.name} readOnly disabled />
      <TextInput
        label={_('Their email')}
        withAsterisk
        type="email"
        autoComplete="off"
        data-autofocus
        {...field(form, 'email')}
      />
      <SimpleGrid cols={{ base: 1, sm: 2 }}>
        <TextInput label={_('First name')} autoComplete="off" {...field(form, 'firstname')} />
        <TextInput label={_('Last name')} autoComplete="off" {...field(form, 'lastname')} />
      </SimpleGrid>
      <Group justify="flex-end" className="form-actions">
        <RequiredNote />
        <Button variant="default" onClick={onDone}>
          {_('Cancel')}
        </Button>
        <Button type="submit" loading={form.processing} leftSection={<IconSend size={18} />}>
          {_('Send the invitation')}
        </Button>
      </Group>
    </Form>
  );
}

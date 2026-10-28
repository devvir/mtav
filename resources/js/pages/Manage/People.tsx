import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { PageHeader } from '@/components/layout/PageHeader';
import { PersonAvatar } from '@/design';
import { confirm } from '@/lib/dialogs';
import { checkbox, nativeSelect } from '@/lib/forms';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated, Person, Ref } from '@/types/models';
import { Link, router, useForm } from '@inertiajs/react';
import {
  ActionIcon,
  Badge,
  Button,
  Card,
  Checkbox,
  Group,
  Modal,
  NativeSelect,
  Stack,
  Text,
} from '@mantine/core';
import { IconMail, IconPencil, IconUserMinus } from '@tabler/icons-react';
import { useState } from 'react';

interface Props {
  members: Paginated<Person>;
  families: Ref[];
  filters: { q: string | null };
}

export default function ManagePeople({ members, families, filters }: Props) {
  const [editing, setEditing] = useState<Person | null>(null);
  const form = useForm({ family_id: '', is_admin: false });
  useProjectChanges({ people: ['members'] });

  const edit = (person: Person) => {
    form.setDefaults({
      family_id: person.family ? String(person.family.id) : '',
      is_admin: person.is_admin,
    });
    form.setData({
      family_id: person.family ? String(person.family.id) : '',
      is_admin: person.is_admin,
    });
    form.clearErrors();
    setEditing(person);
  };

  /** "A project needs at least one admin", and the like */
  const roleError = (form.errors as Record<string, string | undefined>).member;

  const save = () => {
    form.transform((data) => ({ ...data, family_id: data.family_id || null }));
    form.put(route('manage.people.update', editing!.id), {
      preserveScroll: true,
      onSuccess: () => setEditing(null),
    });
  };

  const remove = async (person: Person) => {
    const ok = await confirm({
      title: _('Remove {name} from the project?', { name: person.name }),
      message: _('Their account stays; they just stop taking part in this project.'),
      confirmLabel: _('Remove'),
      danger: true,
    });
    if (ok) router.delete(route('manage.people.destroy', person.id), { preserveScroll: true });
  };

  return (
    <>
      <PageHeader
        title={_('People')}
        description={_('Who takes part in the project, in which family, and who manages it.')}
        actions={
          <Button
            component={Link}
            href={route('manage.invitations.index')}
            leftSection={<IconMail size={18} />}
          >
            {_('Invite people')}
          </Button>
        }
      />
      <div style={{ marginBottom: 16 }}>
        <QuerySearch value={filters.q} placeholder={_('Search by name or email')} maw={440} />
      </div>
      <Card p={0}>
        {members.data.map((person) => (
          <Group key={person.id} className="row" wrap="nowrap">
            <PersonAvatar name={person.name} src={person.avatar} size={40} />
            <div style={{ flex: 1, minWidth: 0 }}>
              <Group gap="xs">
                <Text fw={700}>{person.name}</Text>
                {person.is_admin ? <Badge color="blue">{_('Admin')}</Badge> : null}
              </Group>
              <Text className="ds-caption ds-truncate">
                {person.family?.name ?? _('No family')} · {person.email}
              </Text>
            </div>
            <ActionIcon
              size="lg"
              aria-label={_('Change role or family of {name}', { name: person.name })}
              onClick={() => edit(person)}
            >
              <IconPencil size={18} />
            </ActionIcon>
            <ActionIcon
              size="lg"
              color="red"
              aria-label={_('Remove {name}', { name: person.name })}
              onClick={() => remove(person)}
            >
              <IconUserMinus size={18} />
            </ActionIcon>
          </Group>
        ))}
      </Card>
      <Pagination page={members} />

      <Modal
        opened={Boolean(editing)}
        onClose={() => setEditing(null)}
        title={editing?.name}
        closeOnClickOutside={false}
      >
        <form
          noValidate
          onSubmit={(event) => {
            event.preventDefault();
            save();
          }}
        >
          <Stack gap="md">
            <Text c="dimmed">{_('Their role and family in this project.')}</Text>
            <NativeSelect
              label={_('Family')}
              withAsterisk={!form.data.is_admin}
              data={[
                { value: '', label: _('No family') },
                ...families.map((family) => ({ value: String(family.id), label: family.name })),
              ]}
              {...nativeSelect(form, 'family_id')}
            />
            <Checkbox
              label={_('Admin of the project')}
              description={_('Admins configure the project and run the lottery.')}
              {...checkbox(form, 'is_admin')}
            />
            {roleError ? (
              <Text c="red" size="sm" fw={600} role="alert">
                {roleError}
              </Text>
            ) : null}
            <Group justify="flex-end" gap="sm">
              <Button variant="default" onClick={() => setEditing(null)}>
                {_('Cancel')}
              </Button>
              <Button type="submit" loading={form.processing}>
                {_('Save')}
              </Button>
            </Group>
          </Stack>
        </form>
      </Modal>
    </>
  );
}

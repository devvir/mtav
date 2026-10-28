import { checkbox, nativeSelect } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Person, Ref } from '@/types/models';
import { useForm } from '@inertiajs/react';
import { Button, Checkbox, NativeSelect, Text } from '@mantine/core';
import { Form, FormActions } from './Form';

/** Someone's role and family in the project (in a dialog; `onDone` closes it) */
export function MembershipForm({
  person,
  families,
  onDone,
}: {
  person: Person;
  families: Ref[];
  onDone: () => void;
}) {
  const form = useForm({
    family_id: person.family ? String(person.family.id) : '',
    is_admin: person.is_admin,
  });
  /** "A project needs at least one admin", and the like */
  const roleError = (form.errors as Record<string, string | undefined>).member;

  const save = () => {
    form.transform((data) => ({ ...data, family_id: data.family_id || null }));
    form.put(route('manage.people.update', person.id), { preserveScroll: true, onSuccess: onDone });
  };

  return (
    <Form onSubmit={save}>
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
      <FormActions submit={_('Save')} processing={form.processing}>
        <Button variant="default" onClick={onDone}>
          {_('Cancel')}
        </Button>
      </FormActions>
    </Form>
  );
}

import { field, nativeSelect } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Family, UnitType } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import { Anchor, Card, NativeSelect, Text, TextInput } from '@mantine/core';
import { Form, FormActions } from './Form';

export function FamilyForm({ family, unitTypes }: { family?: Family; unitTypes: UnitType[] }) {
  const form = useForm({
    name: family?.name ?? '',
    unit_type_id: String(family?.unit_type?.id ?? unitTypes[0]?.id ?? ''),
  });
  const submit = () =>
    family
      ? form.put(route('manage.families.update', family.id))
      : form.post(route('manage.families.store'));

  return (
    <Card p="lg" maw={720}>
      <Form onSubmit={submit}>
        <TextInput
          label={_('Name')}
          description={_('Usually its surnames')}
          withAsterisk
          autoFocus
          {...field(form, 'name')}
        />
        <NativeSelect
          label={_('Unit type')}
          description={_('It competes for these units only')}
          withAsterisk
          data={[
            { value: '', label: _('Choose a type'), disabled: true },
            ...unitTypes.map((type) => ({ value: String(type.id), label: type.name })),
          ]}
          {...nativeSelect(form, 'unit_type_id')}
        />
        {!unitTypes.length ? (
          <Text className="ds-caption">
            {_('There are no unit types yet.')}{' '}
            <Anchor component={Link} href={route('manage.unit_types.create')} fw={600}>
              {_('Add one first')}
            </Anchor>
          </Text>
        ) : null}
        <FormActions
          submit={family ? _('Save') : _('Add the family')}
          processing={form.processing}
          cancelHref={route('manage.families.index')}
        />
      </Form>
    </Card>
  );
}

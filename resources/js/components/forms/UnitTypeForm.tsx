import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { UnitType } from '@/types/models';
import { useForm } from '@inertiajs/react';
import { Card, TextInput, Textarea } from '@mantine/core';
import { Form, FormActions } from './Form';

export function UnitTypeForm({ unitType }: { unitType?: UnitType }) {
  const form = useForm({ name: unitType?.name ?? '', description: unitType?.description ?? '' });
  const submit = () =>
    unitType
      ? form.put(route('manage.unit_types.update', unitType.id))
      : form.post(route('manage.unit_types.store'));

  return (
    <Card p="lg" maw={720}>
      <Form onSubmit={submit}>
        <TextInput
          label={_('Name')}
          description={_('For example, 2 bedrooms')}
          withAsterisk
          autoFocus
          {...field(form, 'name')}
        />
        <Textarea label={_('Description')} autosize minRows={3} {...field(form, 'description')} />
        <FormActions
          submit={unitType ? _('Save') : _('Add the unit type')}
          processing={form.processing}
          cancelHref={route('manage.unit_types.index')}
        />
      </Form>
    </Card>
  );
}

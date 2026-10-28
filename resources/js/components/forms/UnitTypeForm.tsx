import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { UnitType } from '@/types/models';
import { useForm } from '@inertiajs/react';
import { Button, TextInput, Textarea } from '@mantine/core';
import { Form, FormActions } from './Form';

/** A unit type: adding one, or changing one (in a dialog; `onDone` closes it) */
export function UnitTypeForm({ unitType, onDone }: { unitType?: UnitType; onDone: () => void }) {
  const form = useForm({ name: unitType?.name ?? '', description: unitType?.description ?? '' });
  const submit = () => {
    const options = { preserveScroll: true, onSuccess: onDone };
    if (unitType) form.put(route('manage.unit_types.update', unitType.id), options);
    else form.post(route('manage.unit_types.store'), options);
  };

  return (
    <Form onSubmit={submit}>
      <TextInput
        label={_('Name')}
        description={_('For example, 2 bedrooms')}
        withAsterisk
        data-autofocus
        {...field(form, 'name')}
      />
      <Textarea label={_('Description')} autosize minRows={3} {...field(form, 'description')} />
      <FormActions
        submit={unitType ? _('Save') : _('Add the unit type')}
        processing={form.processing}
      >
        <Button variant="default" onClick={onDone}>
          {_('Cancel')}
        </Button>
      </FormActions>
    </Form>
  );
}

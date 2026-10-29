import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { UnitType } from '@/types/models';
import { useForm } from '@inertiajs/react';
import { Button, NumberInput, SimpleGrid, Text, TextInput, Textarea } from '@mantine/core';
import { Form, FormActions } from './Form';

/** A unit type: adding one, or changing one (in a dialog; `onDone` closes it) */
export function UnitTypeForm({ unitType, onDone }: { unitType?: UnitType; onDone: () => void }) {
  const form = useForm({
    name: unitType?.name ?? '',
    description: unitType?.description ?? '',
    plan_width: unitType?.plan_size?.[0] ?? ('' as number | ''),
    plan_height: unitType?.plan_size?.[1] ?? ('' as number | ''),
  });
  const errors = form.errors as Record<string, string | undefined>;
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
      <div>
        <SimpleGrid cols={2}>
          {(['plan_width', 'plan_height'] as const).map((key) => (
            <NumberInput
              key={key}
              name={key}
              label={key === 'plan_width' ? _('Width on the plan') : _('Height on the plan')}
              min={2}
              max={2000}
              decimalScale={1}
              value={form.data[key]}
              onChange={(value) => form.setData(key, value === '' ? '' : Number(value))}
              error={errors[key]}
            />
          ))}
        </SimpleGrid>
        <Text className="ds-caption" mt={6}>
          {_('New units of this type start this size on the plan; empty, a plain square.')}
        </Text>
      </div>
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

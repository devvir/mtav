import { field, nativeSelect } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Unit, UnitType } from '@/types/models';
import { useForm } from '@inertiajs/react';
import { Button, Card, Fieldset, NativeSelect, Stack, TextInput } from '@mantine/core';
import { useState } from 'react';
import { Form, FormActions } from './Form';

/**
 * A unit, of an existing type or of a new one created on the way. "Add and continue with
 * another" keeps the type, for loading many units quickly.
 */
export function UnitForm({
  unit,
  unitTypes,
  unitType,
}: {
  unit?: Unit;
  unitTypes: UnitType[];
  unitType?: number | null;
}) {
  const [creatingType, setCreatingType] = useState(!unit && !unitTypes.length);
  const form = useForm({
    identifier: unit?.identifier ?? '',
    unit_type_id: String(unit?.type?.id ?? unitType ?? unitTypes[0]?.id ?? ''),
    new_unit_type: { name: '', description: '' },
    another: false,
  });
  const errors = form.errors as Record<string, string | undefined>;

  const submit = (another: boolean) => {
    form.transform((data) => ({
      ...data,
      another,
      unit_type_id: creatingType ? null : data.unit_type_id,
      new_unit_type: creatingType ? data.new_unit_type : null,
    }));
    form[unit ? 'put' : 'post'](
      unit ? route('manage.units.update', unit.id) : route('manage.units.store'),
      {
        preserveScroll: another,
        onSuccess: (page) => {
          if (!another) return;
          form.reset('identifier');
          // The next unit goes to the type just created
          const created = (page.props as { unitType?: number | null }).unitType;
          if (creatingType && created) {
            setCreatingType(false);
            form.setData((data) => ({
              ...data,
              identifier: '',
              unit_type_id: String(created),
              new_unit_type: { name: '', description: '' },
            }));
          }
        },
      },
    );
  };

  const setNewType = (key: 'name' | 'description', value: string) =>
    form.setData('new_unit_type', { ...form.data.new_unit_type, [key]: value });

  return (
    <Card p="lg" maw={720}>
      <Form onSubmit={() => submit(false)}>
        <TextInput
          data-tour="units.identifier"
          label={_('Identifier')}
          description={_('For example, A-101')}
          placeholder={_('A-101')}
          withAsterisk
          autoFocus
          {...field(form, 'identifier')}
        />
        <Fieldset legend={`${_('Unit type')} *`} data-tour="units.type" variant="unstyled">
          {!creatingType ? (
            <Stack gap="xs">
              <NativeSelect
                aria-label={_('Unit type')}
                data={unitTypes.map((type) => ({ value: String(type.id), label: type.name }))}
                {...nativeSelect(form, 'unit_type_id')}
              />
              {!unit ? (
                <Button
                  variant="subtle"
                  size="compact-sm"
                  w="fit-content"
                  onClick={() => setCreatingType(true)}
                >
                  {_('Or create a new type')}
                </Button>
              ) : null}
            </Stack>
          ) : (
            <Card p="md" bg="var(--mtav-surface-2)">
              <Stack gap="md">
                <TextInput
                  label={_("New type's name")}
                  withAsterisk
                  placeholder={_('2 bedrooms')}
                  name="new_unit_type[name]"
                  value={form.data.new_unit_type.name}
                  onChange={(event) => setNewType('name', event.currentTarget.value)}
                  error={errors['new_unit_type.name']}
                />
                <TextInput
                  label={_('Description')}
                  name="new_unit_type[description]"
                  value={form.data.new_unit_type.description}
                  onChange={(event) => setNewType('description', event.currentTarget.value)}
                  error={errors['new_unit_type.description']}
                />
                {unitTypes.length ? (
                  <Button
                    variant="subtle"
                    size="compact-sm"
                    w="fit-content"
                    onClick={() => setCreatingType(false)}
                  >
                    {_('Use an existing type')}
                  </Button>
                ) : null}
              </Stack>
            </Card>
          )}
        </Fieldset>
        <FormActions
          submit={unit ? _('Save') : _('Add the unit')}
          processing={form.processing}
          cancelHref={route('manage.units.index')}
        >
          {!unit ? (
            <Button
              data-tour="units.another"
              variant="default"
              disabled={form.processing}
              onClick={() => submit(true)}
            >
              {_('Add and continue with another')}
            </Button>
          ) : null}
        </FormActions>
      </Form>
    </Card>
  );
}

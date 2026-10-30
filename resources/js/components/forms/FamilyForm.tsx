import { field, nativeSelect } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Family, UnitType } from '@/types/models';
import { Link, useForm } from '@inertiajs/react';
import { Anchor, Button, NativeSelect, Text, TextInput } from '@mantine/core';
import { Form, FormActions } from './Form';

/**
 * A family's name and type: registering one, changing one, or (`own`) someone with no family
 * starting theirs (in a dialog; `onDone` closes it)
 */
export function FamilyForm({
  family,
  unitTypes,
  own = false,
  onDone,
}: {
  family?: Family;
  unitTypes: Pick<UnitType, 'id' | 'name'>[];
  own?: boolean;
  onDone: () => void;
}) {
  const form = useForm({
    name: family?.name ?? '',
    unit_type_id: String(family?.unit_type?.id ?? unitTypes[0]?.id ?? ''),
  });
  const submit = () => {
    const options = { preserveScroll: true, onSuccess: onDone };
    if (own) form.post(route('families.store'), options);
    else if (family) form.put(route('manage.families.update', family.id), options);
    else form.post(route('manage.families.store'), options);
  };

  return (
    <Form onSubmit={submit}>
      <TextInput
        label={_('Name')}
        description={_('Usually its surnames')}
        withAsterisk
        data-autofocus
        {...field(form, 'name')}
      />
      <NativeSelect
        label={_('Unit type')}
        description={_('It receives a unit of this type')}
        withAsterisk
        data={[
          { value: '', label: _('Choose a type'), disabled: true },
          ...unitTypes.map((type) => ({ value: String(type.id), label: type.name })),
        ]}
        {...nativeSelect(form, 'unit_type_id')}
      />
      {!unitTypes.length && !own ? (
        <Text className="ds-caption">
          {_('There are no unit types yet.')}{' '}
          <Anchor component={Link} href={route('units.index', { new_type: 1 })} fw={600}>
            {_('Add one first')}
          </Anchor>
        </Text>
      ) : null}
      <FormActions
        submit={own ? _('Create our family') : family ? _('Save') : _('Add the family')}
        processing={form.processing}
      >
        <Button variant="default" onClick={onDone}>
          {_('Cancel')}
        </Button>
      </FormActions>
    </Form>
  );
}

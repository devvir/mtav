<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Unit, UnitType } from '@/types/models';
import { useForm } from '@inertiajs/vue3';

/**
 * A unit, of an existing type or of a new one created on the way. "Save and add
 * another" keeps the type, for loading many units quickly.
 */
const props = defineProps<{ unit?: Unit; unitTypes: UnitType[]; unitType?: number | null }>();

const creatingType = ref(!props.unit && !props.unitTypes.length);

const form = useForm({
  identifier: props.unit?.identifier ?? '',
  unit_type_id: props.unit?.type?.id ?? props.unitType ?? props.unitTypes[0]?.id ?? null,
  new_unit_type: null as { name: string; description: string } | null,
  another: false,
});

function submit(another = false) {
  form
    .transform((data) => ({
      ...data,
      another,
      unit_type_id: creatingType.value ? null : data.unit_type_id,
      new_unit_type: creatingType.value ? data.new_unit_type : null,
    }))
    [props.unit ? 'put' : 'post'](
      props.unit ? route('manage.units.update', props.unit.id) : route('manage.units.store'),
      {
        preserveScroll: another,
        onSuccess: () => another && form.reset('identifier'),
      },
    );
}

watch(
  creatingType,
  (creating) => (form.new_unit_type = creating ? { name: '', description: '' } : null),
  { immediate: true },
);
</script>

<template>
  <form
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="submit(false)"
  >
    <Field
      data-tour="units.identifier"
      :label="_('Identifier')"
      :hint="_('How people refer to it: a number, a block and floor…')"
      :error="form.errors.identifier"
      required
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="identifier"
        v-model="form.identifier"
        :placeholder="_('A-101')"
        autofocus
      />
    </Field>

    <fieldset data-tour="units.type" class="space-y-3">
      <legend class="text-sm font-medium">
        {{ _('Unit type') }} <span class="text-error" aria-hidden="true">*</span>
      </legend>

      <template v-if="!creatingType">
        <Field
          :label="_('Unit type')"
          class="[&>label]:sr-only"
          :error="form.errors.unit_type_id"
          v-slot="field"
        >
          <SelectInput
            v-bind="field"
            name="unit_type_id"
            v-model="form.unit_type_id"
            :options="unitTypes.map((type) => ({ value: type.id, label: type.name }))"
          />
        </Field>
        <Button
          v-if="!unit"
          type="button"
          variant="link"
          class="px-0"
          @click="creatingType = true"
          >{{ _('Or create a new type') }}</Button
        >
      </template>

      <div v-else-if="form.new_unit_type" class="space-y-4 rounded-xl bg-surface-sunken p-4">
        <Field
          :label="_('New type\'s name')"
          :error="form.errors['new_unit_type.name']"
          required
          v-slot="field"
        >
          <TextInput
            v-bind="field"
            name="new_unit_type[name]"
            v-model="form.new_unit_type.name"
            :placeholder="_('2 bedrooms')"
          />
        </Field>
        <Field
          :label="_('Description')"
          :error="form.errors['new_unit_type.description']"
          optional
          v-slot="field"
        >
          <TextInput
            v-bind="field"
            name="new_unit_type[description]"
            v-model="form.new_unit_type.description"
          />
        </Field>
        <Button
          v-if="unitTypes.length"
          type="button"
          variant="link"
          class="px-0"
          @click="creatingType = false"
          >{{ _('Use an existing type') }}</Button
        >
      </div>
    </fieldset>

    <FormActions
      :submit-label="unit ? _('Save') : _('Add the unit')"
      :processing="form.processing"
      :cancel-href="route('manage.units.index')"
    >
      <template v-if="!unit" #secondary>
        <Button
          data-tour="units.another"
          type="button"
          variant="outline"
          :disabled="form.processing"
          @click="submit(true)"
          >{{ _('Add and continue with another') }}</Button
        >
      </template>
    </FormActions>
  </form>
</template>

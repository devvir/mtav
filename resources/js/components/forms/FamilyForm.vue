<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import TextInput from '@/components/form/TextInput.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Family, UnitType } from '@/types/models';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{ family?: Family; unitTypes: UnitType[] }>();

const form = useForm({
  name: props.family?.name ?? '',
  unit_type_id: props.family?.unit_type?.id ?? props.unitTypes[0]?.id ?? null,
});

const submit = () =>
  props.family
    ? form.put(route('manage.families.update', props.family.id))
    : form.post(route('manage.families.store'));
</script>

<template>
  <form
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="submit"
  >
    <Field
      :label="_('Name')"
      :hint="_('Usually the family\'s surnames.')"
      :error="form.errors.name"
      required
      v-slot="field"
    >
      <TextInput v-bind="field" name="name" v-model="form.name" autofocus />
    </Field>

    <Field
      :label="_('Unit type')"
      :hint="_('The family competes only for units of this type.')"
      :error="form.errors.unit_type_id"
      required
      v-slot="field"
    >
      <SelectInput
        v-bind="field"
        name="unit_type_id" v-model="form.unit_type_id"
        :options="unitTypes.map((type) => ({ value: type.id, label: type.name }))"
        :placeholder="_('Choose a type')"
      />
    </Field>
    <p v-if="!unitTypes.length" class="text-sm text-text-muted">
      {{ _('There are no unit types yet.') }}
      <Link
        :href="route('manage.unit_types.create')"
        class="font-medium text-text-link hover:underline"
        >{{ _('Add one first') }}</Link
      >
    </p>

    <FormActions
      :submit-label="family ? _('Save') : _('Add the family')"
      :processing="form.processing"
      :cancel-href="route('manage.families.index')"
    />
  </form>
</template>

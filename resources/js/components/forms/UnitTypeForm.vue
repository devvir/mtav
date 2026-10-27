<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import TextArea from '@/components/form/TextArea.vue';
import TextInput from '@/components/form/TextInput.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { UnitType } from '@/types/models';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ unitType?: UnitType }>();

const form = useForm({
  name: props.unitType?.name ?? '',
  description: props.unitType?.description ?? '',
});

const submit = () =>
  props.unitType
    ? form.put(route('manage.unit_types.update', props.unitType.id))
    : form.post(route('manage.unit_types.store'));
</script>

<template>
  <form
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="submit"
  >
    <Field
      :label="_('Name')"
      :hint="_('For example: 2 bedrooms, 3 bedrooms, accessible.')"
      :error="form.errors.name"
      required
      v-slot="field"
    >
      <TextInput v-bind="field" name="name" v-model="form.name" autofocus />
    </Field>
    <Field :label="_('Description')" :error="form.errors.description" optional v-slot="field">
      <TextArea v-bind="field" name="description" v-model="form.description" rows="3" />
    </Field>
    <FormActions
      :submit-label="unitType ? _('Save') : _('Add the unit type')"
      :processing="form.processing"
      :cancel-href="route('manage.unit_types.index')"
    />
  </form>
</template>

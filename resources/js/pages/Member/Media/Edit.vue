<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import TextInput from '@/components/form/TextInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Media } from '@/types/models';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ media: Media }>();

const form = useForm({
  description: props.media.description,
  alt_text: props.media.alt_text ?? '',
});
</script>

<template>
  <PageHeader
    :title="_('Edit the description')"
    :back="{ href: route('media.show', media.id), label: media.description }"
  />

  <form
    novalidate
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="form.put(route('media.update', media.id))"
  >
    <Field :label="_('Description')" :error="form.errors.description" required v-slot="field">
      <TextInput v-bind="field" name="description" v-model="form.description" />
    </Field>
    <Field
      v-if="media.category === 'image'"
      :label="_('Text for people who cannot see the image')"
      :hint="_('A short sentence of what the image shows. Screen readers read it aloud.')"
      :error="form.errors.alt_text"
      optional
      v-slot="field"
    >
      <TextInput v-bind="field" name="alt_text" v-model="form.alt_text" />
    </Field>
    <FormActions
      :submit-label="_('Save')"
      :processing="form.processing"
      :cancel-href="route('media.show', media.id)"
    />
  </form>
</template>

<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import TextArea from '@/components/form/TextArea.vue';
import TextInput from '@/components/form/TextInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Project } from '@/types/models';
import { router, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

const props = defineProps<{ project: Project }>();

const form = useForm({
  name: props.project.name,
  description: props.project.description,
  organization: props.project.organization ?? '',
});

async function destroy() {
  const confirmed = await confirm({
    title: _('Delete {project}?', { project: props.project.name }),
    message: _(
      'Everything in it goes: families, units, files, the lottery and its records. This cannot be undone.',
    ),
    typeToConfirm: props.project.name,
    confirmLabel: _('Delete the project'),
    danger: true,
  });
  if (confirmed)
    router.delete(route('manage.settings.destroy'), { data: { confirmation: props.project.name } });
}
</script>

<template>
  <PageHeader :title="_('Settings')" />

  <form
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="form.put(route('manage.settings.update'), { preserveScroll: true })"
  >
    <Field :label="_('Name')" :error="form.errors.name" required v-slot="field">
      <TextInput v-bind="field" name="name" v-model="form.name" />
    </Field>
    <Field :label="_('Description')" :error="form.errors.description" required v-slot="field">
      <TextArea v-bind="field" name="description" v-model="form.description" />
    </Field>
    <Field :label="_('Organization')" :error="form.errors.organization" optional v-slot="field">
      <TextInput v-bind="field" name="organization" v-model="form.organization" />
    </Field>
    <FormActions :submit-label="_('Save')" :processing="form.processing" />
  </form>

  <section class="mt-8 max-w-2xl rounded-2xl border-2 border-error/40 bg-surface p-5 sm:p-6">
    <h2 class="text-lg font-semibold text-error">{{ _('Delete the project') }}</h2>
    <p class="mt-1 text-text-muted">
      {{ _('Removes the project and everything in it, for everyone.') }}
    </p>
    <Button variant="destructive" class="mt-4" @click="destroy"
      ><Trash2 /> {{ _('Delete the project') }}</Button
    >
  </section>
</template>

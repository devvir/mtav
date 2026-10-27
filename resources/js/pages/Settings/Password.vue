<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import TextInput from '@/components/form/TextInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';

const form = useForm({ current_password: '', password: '', password_confirmation: '' });

const submit = () =>
  form.put(route('password.update'), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
  <PageHeader :title="_('Password')" />

  <form
    novalidate
    class="max-w-xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="submit"
  >
    <Field
      :label="_('Current password')"
      :error="form.errors.current_password"
      required
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="current_password"
        v-model="form.current_password"
        type="password"
        autocomplete="current-password"
      />
    </Field>
    <Field
      :label="_('New password')"
      :hint="_('At least 8 characters.')"
      :error="form.errors.password"
      required
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="password"
        v-model="form.password"
        type="password"
        autocomplete="new-password"
      />
    </Field>
    <Field
      :label="_('Repeat the new password')"
      :error="form.errors.password_confirmation"
      required
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="password_confirmation"
        v-model="form.password_confirmation"
        type="password"
        autocomplete="new-password"
      />
    </Field>
    <FormActions :submit-label="_('Change the password')" :processing="form.processing" />
  </form>
</template>

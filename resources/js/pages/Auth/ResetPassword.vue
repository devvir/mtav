<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ token: string; email: string }>();

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () =>
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
</script>

<template>
  <Head :title="_('Choose a new password')" />
  <h1 class="mb-6 text-2xl font-bold">{{ _('Choose a new password') }}</h1>

  <form class="space-y-5" @submit.prevent="submit">
    <Field :label="_('Email')" :error="form.errors.email" v-slot="field">
      <TextInput
        v-bind="field"
        name="email"
        v-model="form.email"
        type="email"
        autocomplete="email"
        readonly
      />
    </Field>
    <Field
      :label="_('New password')"
      :hint="_('At least 8 characters.')"
      :error="form.errors.password"
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="password"
        v-model="form.password"
        type="password"
        autocomplete="new-password"
        autofocus
      />
    </Field>
    <Field
      :label="_('Repeat the password')"
      :error="form.errors.password_confirmation"
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
    <Button type="submit" class="w-full" :disabled="form.processing">{{
      _('Save the password')
    }}</Button>
  </form>
</template>

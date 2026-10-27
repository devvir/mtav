<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  firstname: '',
  lastname: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () =>
  form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
  <Head :title="_('Create an account')" />
  <h1 class="text-2xl font-bold">{{ _('Create an account') }}</h1>
  <p class="mt-1 mb-6 text-text-muted">
    {{ _('To start a project, or to take part in one you were invited to.') }}
  </p>

  <form class="space-y-5" @submit.prevent="submit">
    <div class="grid gap-5 sm:grid-cols-2">
      <Field :label="_('First name')" :error="form.errors.firstname" required v-slot="field">
        <TextInput
          v-bind="field"
          name="firstname"
          v-model="form.firstname"
          autocomplete="given-name"
          autofocus
        />
      </Field>
      <Field :label="_('Last name')" :error="form.errors.lastname" optional v-slot="field">
        <TextInput
          v-bind="field"
          name="lastname"
          v-model="form.lastname"
          autocomplete="family-name"
        />
      </Field>
    </div>

    <Field :label="_('Email')" :error="form.errors.email" required v-slot="field">
      <TextInput
        v-bind="field"
        name="email"
        v-model="form.email"
        type="email"
        autocomplete="email"
      />
    </Field>

    <Field
      :label="_('Password')"
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
      :label="_('Repeat the password')"
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

    <Button type="submit" class="w-full" :disabled="form.processing">{{
      _('Create the account')
    }}</Button>
  </form>

  <p class="mt-6 text-center text-sm text-text-muted">
    {{ _('Already have an account?') }}
    <Link :href="route('login')" class="font-medium text-text-link hover:underline">{{
      _('Sign in')
    }}</Link>
  </p>
</template>

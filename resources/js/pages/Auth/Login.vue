<script setup lang="ts">
import CheckboxField from '@/components/form/CheckboxField.vue';
import Field from '@/components/form/Field.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
  <Head :title="_('Sign in')" />
  <h1 class="mb-6 text-2xl font-bold">{{ _('Sign in') }}</h1>

  <form class="space-y-5" @submit.prevent="submit">
    <Field :label="_('Email')" :error="form.errors.email" v-slot="field">
      <TextInput
        v-bind="field"
        name="email"
        v-model="form.email"
        type="email"
        autocomplete="email"
        autofocus
      />
    </Field>

    <Field :label="_('Password')" :error="form.errors.password" v-slot="field">
      <TextInput
        v-bind="field"
        name="password"
        v-model="form.password"
        type="password"
        autocomplete="current-password"
      />
    </Field>

    <div class="flex items-center justify-between gap-4">
      <CheckboxField name="remember" v-model="form.remember" :label="_('Keep me signed in')" />
      <Link
        :href="route('password.request')"
        class="text-sm font-medium text-text-link hover:underline"
      >
        {{ _('Forgot your password?') }}
      </Link>
    </div>

    <Button type="submit" class="w-full" :disabled="form.processing">{{ _('Sign in') }}</Button>
  </form>

  <p class="mt-6 text-center text-sm text-text-muted">
    {{ _('New here?') }}
    <Link :href="route('register')" class="font-medium text-text-link hover:underline">{{
      _('Create an account')
    }}</Link>
  </p>
</template>

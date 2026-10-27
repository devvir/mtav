<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '' });
</script>

<template>
  <Head :title="_('Forgot your password?')" />
  <h1 class="text-2xl font-bold">{{ _('Forgot your password?') }}</h1>
  <p class="mt-1 mb-6 text-text-muted">{{ _('We will email you a link to choose a new one.') }}</p>

  <form novalidate class="space-y-5" @submit.prevent="form.post(route('password.email'))">
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
    <Button type="submit" class="w-full" :disabled="form.processing">{{
      _('Send the link')
    }}</Button>
  </form>

  <p class="mt-6 text-center text-sm">
    <Link :href="route('login')" class="font-medium text-text-link hover:underline">{{
      _('Back to sign in')
    }}</Link>
  </p>
</template>

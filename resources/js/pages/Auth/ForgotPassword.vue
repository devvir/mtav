<script setup lang="ts">
import Head from '@/components/Head.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { _ } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
  email: '',
});

const submit = () => form.post(route('password.email'));
</script>

<script lang="ts">
export default { layout: null };
</script>

<template>
  <Head title="Forgot Password" />

  <AuthLayout
    title="Forgot Password"
    description="Enter your email to receive a password reset link"
  >
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid gap-2">
          <Label for="email">{{ _('Email address') }}</Label>
          <Input
            id="email"
            type="email"
            name="email"
            autocomplete="off"
            v-model="form.email"
            autofocus
            :placeholder="_('email@example.com')"
          />
          <InputError :message="form.errors.email" />
        </div>

      <div class="flex items-center justify-start">
        <Button class="w-full" :disabled="form.processing">
          <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
          {{ _('Email password reset link') }}
        </Button>
      </div>
    </form>

    <div class="space-x-1 text-center text-sm text-text-muted">
      <span>{{ _('Or, return to') }}</span>
      <TextLink :href="route('login')">{{ _('Log in') }}</TextLink>
    </div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Head, router, useForm } from '@inertiajs/vue3';
import { MailCheck } from 'lucide-vue-next';

defineProps<{ email: string }>();

const form = useForm({});
</script>

<template>
  <Head :title="_('Confirm your email')" />
  <MailCheck class="mb-4 size-12 text-interactive" />
  <h1 class="text-2xl font-bold">{{ _('Confirm your email') }}</h1>
  <p class="mt-2 text-text-muted">
    {{ _('We sent a link to {email}. Open it to confirm your address and continue.', { email }) }}
  </p>

  <div class="mt-6 flex flex-col gap-3">
    <Button :disabled="form.processing" @click="form.post(route('verification.send'))">{{
      _('Send the link again')
    }}</Button>
    <Button variant="ghost" @click="router.post(route('logout'))">{{ _('Sign out') }}</Button>
  </div>
</template>

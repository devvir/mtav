<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Head, Link } from '@inertiajs/vue3';
import { MailX } from 'lucide-vue-next';
</script>

<template>
  <Head :title="_('Invitation not valid')" />
  <MailX class="mb-4 size-12 text-text-subtle" />
  <h1 class="text-2xl font-bold">{{ _('This invitation is no longer valid') }}</h1>
  <p class="mt-2 text-text-muted">
    {{
      _(
        'It may have expired, been withdrawn, or been used already. Ask whoever invited you to send a new one.',
      )
    }}
  </p>
  <Button class="mt-6 w-full" as-child>
    <Link :href="route('login')">{{ _('Go to sign in') }}</Link>
  </Button>
</template>

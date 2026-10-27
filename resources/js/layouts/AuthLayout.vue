<script setup lang="ts">
import Toaster from '@/components/feedback/Toaster.vue';
import DialogHost from '@/components/modal/DialogHost.vue';
import { route } from '@/composables/useRoute';
import { Link, usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';

/**
 * Signing in, signing up, invitations: a simple centred card.
 */
const page = usePage();
</script>

<template>
  <div class="flex min-h-dvh flex-col items-center justify-center px-4 py-10">
    <Link :href="route('home')" class="mb-8 flex items-center gap-2 text-2xl font-bold">
      <Building2 class="size-8 text-interactive" /> {{ page.props.name }}
    </Link>
    <main
      class="w-full max-w-md rounded-2xl border-2 border-border bg-surface p-6 shadow-sm sm:p-8"
    >
      <slot />
    </main>
    <Toaster />
    <DialogHost />
  </div>
</template>

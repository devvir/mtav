<script setup lang="ts">
// Copilot - pending review
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';

defineProps<{
  title: string;
  viewAllHref?: string;
  viewAllText?: string;
  align?: 'left' | 'right';
}>();
</script>

<template>
  <div class="mb-3 flex items-center justify-between gap-4">
    <Link
      v-if="viewAllHref"
      :href="route(viewAllHref)"
      :class="['flex-1', align === 'right' ? 'text-right' : '']"
    >
      <h2 class="text-lg font-semibold text-text hover:text-text-link focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset focus:outline-none">
        {{ title }}
      </h2>
    </Link>
    <h2 v-else :class="['flex-1 text-lg font-semibold text-text', align === 'right' ? 'text-right' : '']">
      {{ title }}
    </h2>

    <Link
      v-if="viewAllText"
      :href="route(viewAllHref)"
      class="shrink-0 text-sm text-text-link hover:text-text-link-hover hover:underline focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset focus:outline-none"
    >
      {{ viewAllText || _('View all') }}
    </Link>
  </div>
</template>

<script setup lang="ts">
// Copilot - pending review
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
    <Link v-if="viewAllHref" :href="viewAllHref" :class="['flex-1', align === 'right' ? 'text-right' : '']">
      <h2 class="text-lg font-semibold hover:text-primary">{{ title }}</h2>
    </Link>
    <h2 v-else :class="['flex-1 text-lg font-semibold', align === 'right' ? 'text-right' : '']">{{ title }}</h2>

    <Link v-if="viewAllHref" :href="viewAllHref" class="shrink-0 text-sm text-primary hover:underline">
      {{ viewAllText || 'View all' }}
    </Link>
  </div>
</template>

<script setup lang="ts">
// Copilot - pending review
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

defineProps<{
  title: string;
  value?: string | number;
  icon?: LucideIcon;
  href?: string;
  subtitle?: string;
}>();
</script>

<template>
  <component
    :is="href ? Link : 'div'"
    :href="href"
    class="flex items-center gap-4 rounded-lg border border-border bg-surface-elevated p-4 transition-all hover:border-border-interactive hover:shadow-md focus-within:ring-2 focus-within:ring-focus-ring focus-within:ring-offset-2 focus-within:ring-offset-focus-ring-offset"
    :class="{ 'cursor-pointer': href }"
  >
    <div v-if="icon" class="flex h-12 w-12 items-center justify-center rounded-lg bg-interactive/10">
      <component :is="icon" class="h-6 w-6 text-interactive" />
    </div>
    <div class="flex-1">
      <div class="text-2xl font-bold text-text">{{ value ?? '...' }}</div>
      <div class="text-sm text-text-muted">{{ title }}</div>
      <div v-if="subtitle" class="text-xs text-text-subtle">{{ subtitle }}</div>
    </div>
  </component>
</template>

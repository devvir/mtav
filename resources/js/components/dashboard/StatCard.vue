<script setup lang="ts">
// Copilot - pending review
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

defineProps<{
  title: string;
  value: string | number;
  icon?: LucideIcon;
  href?: string;
  subtitle?: string;
}>();
</script>

<template>
  <component
    :is="href ? Link : 'div'"
    :href="href"
    class="flex items-center gap-4 rounded-lg border border-sidebar-border/70 bg-white p-4 transition-all hover:shadow-md dark:border-sidebar-border dark:bg-sidebar"
    :class="{ 'cursor-pointer': href }"
  >
    <div v-if="icon" class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
      <component :is="icon" class="h-6 w-6 text-primary" />
    </div>
    <div class="flex-1">
      <div class="text-2xl font-bold text-foreground">{{ value }}</div>
      <div class="text-sm text-muted-foreground">{{ title }}</div>
      <div v-if="subtitle" class="text-xs text-muted-foreground">{{ subtitle }}</div>
    </div>
  </component>
</template>

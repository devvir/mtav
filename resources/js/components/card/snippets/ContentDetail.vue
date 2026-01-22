<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';

defineProps<{
  icon: LucideIcon;
  title: string;
  content?: string;
  fallback?: string;
  href?: string;
  linkClass?: string;
  contentClass?: string;
}>();
</script>

<template>
  <div class="grid grid-cols-[auto_1fr] gap-3">
    <component :is="icon" class="mt-0.5 h-5 w-5 text-text-muted" />
    <div class="min-w-0">
      <div class="text-sm font-medium">{{ title }}</div>
      <div class="truncate text-sm text-text-muted" :class="contentClass">
        <a
          v-if="href && (content || fallback)"
          :href="href"
          target="_blank"
          rel="noopener noreferrer"
          :class="linkClass || 'text-interactive underline hover:text-interactive-hover'"
          class="truncate"
        >
          {{ content || fallback }}
        </a>
        <span v-else class="truncate">{{ content || fallback }}</span>
      </div>
    </div>
  </div>
</template>

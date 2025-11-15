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
    <component :is="icon" class="h-5 w-5 mt-0.5 text-text-muted" />
    <div class="min-w-0">
      <div class="font-medium text-sm">{{ title }}</div>
      <div class="text-text-muted text-sm truncate" :class="contentClass">
        <a
          v-if="href && (content || fallback)"
          :href="href"
          target="_blank"
          rel="noopener noreferrer"
          :class="linkClass || 'text-interactive hover:text-interactive-hover underline'"
          class="truncate"
        >
          {{ content || fallback }}
        </a>
        <span v-else class="truncate">{{ content || fallback }}</span>
      </div>
    </div>
  </div>
</template>
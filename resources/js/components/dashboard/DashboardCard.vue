<script setup lang="ts">
// Copilot - pending review
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
  title?: string;
  subtitle?: string;
  image?: string;
  href?: string;
  badge?: string | number;
}>();

const hasSlot = computed(() => !!props.title || !!props.subtitle);
</script>

<template>
  <component
    :is="href ? Link : 'div'"
    :href="href"
    class="group relative flex flex-col overflow-hidden rounded-lg border border-border bg-surface-elevated transition-all focus-within:ring-2 focus-within:ring-focus-ring focus-within:ring-offset-2 focus-within:ring-offset-focus-ring-offset hover:border-border-interactive hover:shadow-md"
    :class="{ 'cursor-pointer': href }"
  >
    <div v-if="image" class="relative aspect-video w-full overflow-hidden bg-surface-sunken">
      <img
        :src="image"
        :alt="title"
        class="h-full w-full object-cover transition-transform group-hover:scale-105"
      />
      <div
        v-if="badge !== undefined"
        class="absolute top-2 right-2 rounded-full bg-surface/90 px-2 py-0.5 text-xs font-medium text-text backdrop-blur-sm"
      >
        {{ badge }}
      </div>
    </div>

    <div v-if="hasSlot || $slots.default" class="flex flex-1 flex-col gap-1 p-3">
      <div v-if="title" class="text-sm font-semibold text-text">{{ title }}</div>
      <div v-if="subtitle" class="text-xs text-text-muted">{{ subtitle }}</div>
      <slot />
    </div>
  </component>
</template>

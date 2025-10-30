<script setup lang="ts">
// Copilot - pending review
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    class="group relative flex flex-col overflow-hidden rounded-lg border border-sidebar-border/70 bg-white transition-all hover:shadow-md dark:border-sidebar-border dark:bg-sidebar"
    :class="{ 'cursor-pointer': href }"
  >
    <div v-if="image" class="relative aspect-video w-full overflow-hidden bg-muted">
      <img :src="image" :alt="title" class="h-full w-full object-cover transition-transform group-hover:scale-105" />
      <div
        v-if="badge !== undefined"
        class="absolute right-2 top-2 rounded-full bg-black/60 px-2 py-0.5 text-xs font-medium text-white backdrop-blur-sm"
      >
        {{ badge }}
      </div>
    </div>

    <div v-if="hasSlot || $slots.default" class="flex flex-1 flex-col gap-1 p-3">
      <div v-if="title" class="text-sm font-semibold text-foreground">{{ title }}</div>
      <div v-if="subtitle" class="text-xs text-muted-foreground">{{ subtitle }}</div>
      <slot />
    </div>
  </component>
</template>

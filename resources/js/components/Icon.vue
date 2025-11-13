<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import * as icons from 'lucide-vue-next';

const props = withDefaults(defineProps<{
  name: string;
  size?: number | string;
  color?: string;
  strokeWidth?: number | string;
  class?: HTMLAttributes['class'];
}>(), {
  size: 16,
  strokeWidth: 2,
});

const icon = computed(() => {
  const iconName = props.name.charAt(0).toUpperCase() + props.name.slice(1);
  return (icons as Record<string, any>)[iconName];
});
</script>

<template>
  <component
    :is="icon"
    :size="props.size"
    :color="props.color"
    :stroke-width="props.strokeWidth"
    :class="cn('h-4 w-4', $props.class)"
  />
</template>
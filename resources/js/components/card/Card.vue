<script setup lang="ts">
import { cn } from '@/lib/utils';
import * as exposed from './exposed';

const props = defineProps<{
  resource?: ApiResource;
  editUrl?: string;
  class?: string;
}>();

provide(exposed.resource, props.resource);
provide(exposed.editUrl, props.editUrl);
</script>

<template>
  <article
    :class="cn(
      'w-full max-w-3xl h-full',
      'bg-surface-elevated border border-border rounded-lg shadow-sm',
      'flex flex-col', // Use flexbox for footer alignment
      '[&>*+*]:border-t [&>*+*]:border-border',
      props.class
    )"
  >
    <slot />
  </article>
</template>
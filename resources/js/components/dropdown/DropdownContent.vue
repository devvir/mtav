<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: any;
}>();

const isOpen = inject<boolean>('isOpen');
</script>

<template>
  <div role="dropdown" :class="cn(['absolute top-full z-20 w-full', { invisible: !isOpen }], props.class ?? '')">
    <slot />
  </div>
</template>

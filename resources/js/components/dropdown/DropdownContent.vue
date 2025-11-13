<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import * as keys from './keys';

defineProps<{
  class?: HTMLAttributes['class'];
}>();

const isOpen = inject<boolean>(keys.isOpen);
</script>

<template>
  <div
    class="dropdown-content"
    role="dropdown"
    :class="cn(['absolute top-full z-20 w-full', { invisible: !isOpen }], $props.class ?? '')"
  >
    <slot />
  </div>
</template>

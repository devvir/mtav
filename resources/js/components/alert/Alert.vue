<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { type AlertVariant, alertVariants } from '.';

const props = defineProps<{
  variant?: AlertVariant;
  class?: HTMLAttributes['class'];
}>();

const alertClasses = alertVariants({ variant: props.variant });
</script>

<template>
  <div role="alert" :class="cn(alertClasses, $props.class)">
    <slot />
  </div>
</template>

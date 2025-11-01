<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue';
import { type AlertVariant, alertVariants } from '.';
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
  variant?: AlertVariant;
}>();

const alertClass = computed(() => cn(alertVariants({ variant: props.variant }), props.class));
</script>

<template>
  <div role="alert" :class="alertClass">
    <slot />
  </div>
</template>

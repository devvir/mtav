<script setup lang="ts">
import { cn } from '@/lib/utils';

withDefaults(defineProps<{ tone?: 'neutral' | 'success' | 'warning' | 'error' | 'info' }>(), {
  tone: 'neutral',
});

const tones = {
  neutral: 'bg-surface-sunken text-text-muted',
  success: 'bg-success-subtle text-success-subtle-foreground',
  warning: 'bg-warning-subtle text-warning-subtle-foreground',
  error: 'bg-error-subtle text-error-subtle-foreground',
  info: 'bg-info-subtle text-info-subtle-foreground',
};
</script>

<template>
  <span
    :class="
      cn(
        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap',
        tones[tone],
      )
    "
  >
    <slot />
  </span>
</template>

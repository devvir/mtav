<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import type { BadgeSize, BadgeVariant } from '.';

defineProps<{
  variant?: BadgeVariant;
  size?: BadgeSize;
  class?: HTMLAttributes['class'];
}>();

const variantClasses: Record<BadgeVariant, string> = {
  default: 'bg-surface-elevated text-text border border-border',
  secondary: 'bg-surface text-text-muted',
  danger: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  outline: 'bg-background text-text hover:bg-surface',
  success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  info: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
};

const sizeClasses: Record<BadgeSize, string> = {
  sm: 'px-2 py-0.5 text-xs',
  md: 'px-2.5 py-0.5 text-xs',
  lg: 'px-3 py-1 text-sm',
};
</script>

<template>
  <span
    :class="
      cn(
        'inline-flex items-center rounded-full font-medium transition-colors',
        variantClasses[variant || 'default'],
        sizeClasses[size || 'md'],
        $props.class,
      )
    "
  >
    <slot />
  </span>
</template>

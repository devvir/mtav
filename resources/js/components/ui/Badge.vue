<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
  variant?: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' | 'info';
  size?: 'sm' | 'md' | 'lg';
  class?: string;
}>();

const getVariantClasses = (variant: string) => {
  switch (variant) {
    case 'secondary':
      return 'bg-surface text-text-muted';
    case 'destructive':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    case 'outline':
      return 'border border-border bg-background text-text hover:bg-surface';
    case 'success':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    case 'warning':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    case 'info':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    default: // 'default'
      return 'bg-surface-elevated text-text border border-border';
  }
};

const getSizeClasses = (size: string) => {
  switch (size) {
    case 'sm':
      return 'px-2 py-0.5 text-xs';
    case 'lg':
      return 'px-3 py-1 text-sm';
    default: // 'md'
      return 'px-2.5 py-0.5 text-xs';
  }
};
</script>

<template>
  <span
    :class="cn(
      'inline-flex items-center rounded-full font-medium transition-colors',
      getVariantClasses(props.variant || 'default'),
      getSizeClasses(props.size || 'md'),
      props.class
    )"
  >
    <slot />
  </span>
</template>
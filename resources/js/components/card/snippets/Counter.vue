<script setup lang="ts">
import { _ } from '@/composables/useTranslations';

defineProps<{
  count: number;
  singular: string;
  plural: string;
  size?: 'sm' | 'md' | 'lg';
}>();

const sizeClasses = {
  sm: 'text-xl',
  md: 'text-3xl',
  lg: 'text-4xl',
};
</script>

<template>
  <div class="text-sm whitespace-nowrap text-text-muted">
    <span :class="['mr-2 font-extrabold', sizeClasses[size || 'md']]">{{ count }}</span>
    {{ count === 1 ? _(singular) : _(plural) }}
  </div>
</template>

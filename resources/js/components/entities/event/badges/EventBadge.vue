<script setup lang="ts">
import { Badge } from '@/components/badge';
import type { EventBadgeConfig } from './useEventBadges';

const props = defineProps<{
  config: EventBadgeConfig;
}>();

const badgeClass = computed(() => {
  const { variant, type } = props.config;

  // Gentle font weights - no aggressive typography
  const fontWeight = type === 'event-type' ? 'font-medium' : 'font-normal';

  const variantStyles: Record<string, string> = {
    // Event types - warm, distinctive with better contrast
    lottery:
      'bg-amber-100 text-amber-950 border border-amber-300 dark:bg-amber-900/30 dark:text-amber-50 dark:border-amber-600/50', // Slightly more prominent border
    online: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-100',
    onsite:
      'bg-sky-100 text-sky-800 border border-sky-300 dark:bg-sky-900/40 dark:text-sky-100 dark:border-sky-600/50', // Slightly more prominent border

    // Draft status - better contrast for readability
    draft: 'bg-orange-50/40 text-orange-700 dark:bg-orange-900/15 dark:text-orange-400',

    // RSVP - less important, using a quieter color
    rsvp: 'bg-violet-50 text-violet-800 dark:bg-violet-900/25 dark:text-violet-200',

    // Status - improved readability while maintaining context
    completed: 'bg-gray-100 text-gray-600 dark:bg-gray-800/40 dark:text-gray-400 opacity-75',
    ongoing: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-100',
    upcoming: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-100',
    'no-date':
      'bg-gray-50/80 text-gray-400 border border-dashed border-gray-400 dark:bg-gray-900/15 dark:text-gray-500 dark:border-gray-600 opacity-60', // Prominent border for size perception
  };

  return [fontWeight, variantStyles[variant] || 'bg-gray-100 text-gray-700', 'px-2.5 py-1']
    .filter(Boolean)
    .join(' ');
});
</script>

<template>
  <Badge variant="default" size="sm" :class="badgeClass">
    {{ config.text }}
  </Badge>
</template>

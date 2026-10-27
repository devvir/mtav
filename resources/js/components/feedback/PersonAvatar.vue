<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<{ name: string; src?: string | null; size?: 'sm' | 'md' | 'lg' }>(),
  { size: 'md' },
);

const initials = computed(() =>
  props.name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0]?.toUpperCase())
    .join(''),
);

/** A stable, pleasant hue per name */
const hue = computed(
  () => [...props.name].reduce((sum, char) => sum + char.charCodeAt(0), 0) % 360,
);

const sizes = { sm: 'size-8 text-xs', md: 'size-10 text-sm', lg: 'size-16 text-xl' };
</script>

<template>
  <img v-if="src" :src :alt="name" :class="cn('shrink-0 rounded-full object-cover', sizes[size])" />
  <span
    v-else
    aria-hidden="true"
    :class="
      cn(
        'flex shrink-0 items-center justify-center rounded-full font-semibold text-white',
        sizes[size],
      )
    "
    :style="{ backgroundColor: `hsl(${hue} 45% 42%)` }"
  >
    {{ initials }}
  </span>
</template>

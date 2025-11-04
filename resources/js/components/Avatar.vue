<script setup lang="ts">
import { Avatar as AvatarRoot, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';

interface Props {
  subject: Subject;
  size?: 'sm' | 'md' | 'lg';
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
});

const { getInitials } = useInitials();

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'size-8 text-xs';
    case 'lg':
      return 'size-16 text-lg';
    case 'md':
    default:
      return 'size-12 text-sm';
  }
});

// Determine border radius - use rounded-full if specified in class, otherwise rounded-lg
const isRoundedFull = computed(() => props.class?.includes('rounded-full'));
const borderRadius = computed(() => isRoundedFull.value ? 'rounded-full' : 'rounded-lg');
</script>

<template>
  <AvatarRoot :class="cn('overflow-hidden', borderRadius, sizeClasses, props.class)">
    <AvatarImage v-if="subject.avatar" :src="subject.avatar" :alt="subject.name" />
    <AvatarFallback :class="cn(borderRadius, 'bg-surface-interactive text-text font-semibold')">
      {{ getInitials(subject.name) }}
    </AvatarFallback>
  </AvatarRoot>
</template>

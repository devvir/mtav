<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { Avatar as AvatarRoot, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { AvatarSize } from '.';

defineProps<{
  subject: Subject;
  size?: AvatarSize;
  class?: HTMLAttributes['class'];
}>();

const sizes: Record<AvatarSize, string> = {
    xs: 'size-6 text-[0.6rem]',
    sm: 'size-8 text-xs',
    md: 'size-12 text-sm',
    lg: 'size-16 text-lg',
};

const { getInitials } = useInitials();
</script>

<template>
  <AvatarRoot :class="cn(
    'overflow-hidden rounded-lg',
    sizes[size || 'md'],
    $props.class
  )">
    <AvatarImage v-if="subject.avatar" :src="subject.avatar" :alt="subject.name" />
    <AvatarFallback class="bg-surface-interactive text-text font-semibold">
      {{ getInitials(subject.name) }}
    </AvatarFallback>
  </AvatarRoot>
</template>

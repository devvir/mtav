<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import CardBox from './CardBox.vue';

defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <CardBox :class="cn('h-full p-wide text-text', $props.class)" v-bind="$attrs">
    <template v-if="$slots.header" v-slot:header>
      <slot name="header" />
    </template>

    <template v-slot:default>
      <slot />
    </template>
  </CardBox>
</template>

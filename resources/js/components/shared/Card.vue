<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import CardBox from './CardBox.vue';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

const attrs = useAttrs();
</script>

<template>
  <CardBox :class="cn('h-full p-wide text-text', props.class)" v-bind="attrs">
    <template v-if="$slots.header" v-slot:header>
      <slot name="header" />
    </template>

    <template v-slot:default>
      <slot />
    </template>
  </CardBox>
</template>

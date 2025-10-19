<script setup lang="ts">
import { cn } from '@/lib/utils';
import CardBox from './CardBox.vue';

const props = defineProps<{
  class?: any;
}>();

const attrs = useAttrs();
</script>

<template>
  <CardBox :class="cn('h-full px-wide py-wide-y text-foreground', props.class)" v-bind="attrs">
    <template v-if="$slots.header" v-slot:header>
      <slot name="header" />
    </template>

    <template v-slot:default>
      <slot />
    </template>
  </CardBox>
</template>

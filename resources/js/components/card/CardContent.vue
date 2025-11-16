<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { type CardType } from '.';
import * as exposed from './exposed';

defineProps<{
  class?: HTMLAttributes['class'];
}>();

const cardType = inject(exposed.type) as CardType;
const routes = inject(exposed.routes);
</script>

<template>
  <main :class="cn(
    'flex flex-1 flex-col items-start gap-4',
    $props.class,
    '@container/card-content',
    'relative min-w-0'
  )">
    <slot :card-type="cardType" :entity-routes="routes" />
  </main>
</template>

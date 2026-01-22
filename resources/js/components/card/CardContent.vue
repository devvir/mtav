<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { type CardType } from '.';
import * as exposed from './exposed';

defineProps<{
  class?: HTMLAttributes['class'];
}>();

const resource = inject(exposed.resource, {}) as ApiResource;
const cardType = inject(exposed.type, {}) as CardType;
const routes = inject(exposed.routes, {}) as Record<ResourceAction, string>;
</script>

<template>
  <main
    :class="
      cn(
        'flex flex-1 flex-col items-start gap-4',
        'relative min-w-0',
        $props.class,
        '@container/card-content',
      )
    "
  >
    <slot :resource :card-type="cardType" :entity-routes="routes" />
  </main>
</template>

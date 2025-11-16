<script setup lang="ts">
import { cn } from '@/lib/utils';
import { type CardType, CreatedMeta } from '.';
import * as exposed from './exposed';

const cardType = inject(exposed.type) as CardType;
const routes = inject(exposed.routes);
</script>

<template>
  <footer :class="cn('block text-right text-xs text-text-subtle', $attrs.class, 'mt-auto')">
    <slot :card-type="cardType" :entity-routes="routes">
      <CreatedMeta />
    </slot>
  </footer>
</template>

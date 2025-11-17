<script setup lang="ts">
import { cn } from '@/lib/utils';
import { type CardType, CreatedMeta } from '.';
import * as exposed from './exposed';

const resource = inject(exposed.resource, {}) as ApiResource;
const cardType = inject(exposed.type, {}) as CardType;
const routes = inject(exposed.routes, {}) as Record<ResourceAction, string>;
</script>

<template>
  <footer :class="cn('block text-right text-xs text-text-subtle', $attrs.class, 'mt-auto')">
    <slot :resource :card-type="cardType" :entity-routes="routes">
      <CreatedMeta />
    </slot>
  </footer>
</template>

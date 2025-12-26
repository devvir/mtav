<script setup lang="ts">
import type { PolygonConfig, AutoScale } from './types';
import Canvas from './core/Canvas.vue';

interface Props {
  plan: ApiResource<Plan>;
  autoScale?: AutoScale;
  highlightedItemId?: number;
}

const emit = defineEmits<{
  itemClick: [id: number];
  itemHover: [id: number, hovering: boolean];
}>();

const {
  plan,
  autoScale,
  highlightedItemId,
} = defineProps<Props>();

/**
 * Create boundary from plan polygon
 */
const boundary = computed((): PolygonConfig | undefined => {
  if (! plan?.polygon) {
    return undefined;
  }

  return {
    points: plan.polygon,
    stroke: '#94a3b8',
    strokeWidth: 2,
  };
});

/**
 * Canvas configuration from plan
 */
const config = computed(() => ({
  width: parseInt(plan?.width || '800'),
  height: parseInt(plan?.height || '600'),
  bgColor: 'none',
}));

const handleItemClick = (id: number) => emit('itemClick', id);
const handleItemHover = (id: number, hovering: boolean) => emit('itemHover', id, hovering);
</script>

<template>
  <Canvas :config :autoScale :highlightedItemId
    :boundary
    :items="plan.items || []"
    @item-click="handleItemClick"
    @item-hover="handleItemHover"
  />
</template>

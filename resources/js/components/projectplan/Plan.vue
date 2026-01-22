<script setup lang="ts">
import Canvas from './core/Canvas.vue';
import type { PolygonConfig, ScaleMode } from './types';

interface Props {
  plan: ApiResource<Plan>;
  scaleMode?: ScaleMode;
  highlightedItemId?: number;
}

const emit = defineEmits<{
  itemClick: [id: number];
  itemHover: [id: number, hovering: boolean];
}>();

const { plan, scaleMode, highlightedItemId } = defineProps<Props>();

/**
 * Create boundary from plan polygon
 */
const boundary = computed(
  (): PolygonConfig => ({
    polygon: plan.polygon,
    stroke: '#94a3b8',
    strokeWidth: 2,
  }),
);

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
  <Canvas
    :config
    :scaleMode
    :highlightedItemId
    :boundary
    :items="plan.items || []"
    @item-click="handleItemClick"
    @item-hover="handleItemHover"
  />
</template>

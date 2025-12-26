<script setup lang="ts">
import type { PolygonConfig, AutoScale, CanvasConfig } from '../types';
import { useScaling } from '../composables/useScaling';
import Item from '../Item.vue';
import Boundary from '../components/Boundary.vue';

interface Props {
  items: PlanItem[];
  boundary?: PolygonConfig;
  highlightedItemId?: number;
  autoScale?: AutoScale;
  config?: CanvasConfig;
}

const emit = defineEmits<{
  itemClick: [id: number];
  itemHover: [id: number, hovering: boolean];
}>();

const {
  items = [],
  boundary,
  highlightedItemId,
  autoScale = 'contain',
  config = { width: 800, height: 600, bgColor: 'transparent' },
} = defineProps<Props>();

// Local hover state
const hoveredItemId = ref<number>();

const {
  items: scaledItems,
  boundary: scaledBoundary,
  viewBox,
} = useScaling(items, boundary, config, autoScale);

const isItemHighlighted = (itemId: number): boolean => itemId === highlightedItemId;

const handleItemClick = (id: number) => emit('itemClick', id);
const handleItemHover = (id: number, hovering: boolean) => {
  if (hovering) {
    hoveredItemId.value = id;
  } else if (hoveredItemId.value === id) {
    hoveredItemId.value = undefined;
  }

  emit('itemHover', id, hovering);
}
</script>

<template>
  <svg
    :viewBox
    :style="{ backgroundColor: config?.bgColor || '#f8fafc' }"
    class="block w-full"
    preserveAspectRatio="xMidYMid meet"
  >
    <!-- Boundary layer (background) -->
    <Boundary
      v-if="boundary"
      :points="scaledBoundary.points"
      :fill="boundary.fill"
      :stroke="boundary.stroke"
      :stroke-width="boundary.strokeWidth"
      :opacity="boundary.opacity"
    />

    <!-- Items layer -->
    <Item v-for="item in scaledItems" :key="item.id"
      :item
      :highlighted="isItemHighlighted(item.id)"
      @click="handleItemClick"
      @hover="handleItemHover"
    />
  </svg>
</template>

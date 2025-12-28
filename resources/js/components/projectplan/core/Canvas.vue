<script setup lang="ts">
import type { PolygonConfig, ScaleMode } from '../types';
import { useScaling } from '../composables/useScaling';
import Item from '../Item.vue';
import Boundary from '../components/Boundary.vue';

interface Props {
  items: PlanItem[];
  boundary?: PolygonConfig;
  highlightedItemId?: number;
  scaleMode?: ScaleMode;
  forceRatio?: number;
  bgColor?: string;
}

const emit = defineEmits<{
  itemHover: [id: number, hovering: boolean];
  itemClick: [id: number, event: MouseEvent];
  itemMousemove: [id: number, event: MouseEvent];
  itemMousedown: [id: number, event: MouseEvent];
  itemMouseup: [id: number, event: MouseEvent];
}>();

const {
  items = [],
  boundary,
  highlightedItemId,
  scaleMode = 'contain',
  bgColor = 'transparent',
  forceRatio,
} = defineProps<Props>();

const { scale } = useScaling(scaleMode);

const scaleResult = computed(() => scale(items, boundary, forceRatio));

const viewBox = computed(() => scaleResult.value.viewBox);
const scaledItems = computed(() => scaleResult.value.items);
const scaledBoundary = computed(() => scaleResult.value.boundary);

const isItemHighlighted = (itemId: number): boolean => itemId === highlightedItemId;

// Local hover state
const hoveredItemId = ref<number>();

const handleItemHover = (id: number, hovering: boolean) => {
  if (hovering) hoveredItemId.value = id;
  else if (hoveredItemId.value === id) hoveredItemId.value = undefined;

  emit('itemHover', id, hovering);
}
</script>

<template>
  <svg
    :viewBox
    :style="{ backgroundColor: bgColor || '#f8fafc' }"
    class="block w-full"
    preserveAspectRatio="xMidYMid meet"
  >
    <!-- Boundary layer (background) -->
    <Boundary
      v-if="boundary"
      :polygon="scaledBoundary.polygon"
      :fill="boundary.fill"
      :stroke="boundary.stroke"
      :stroke-width="boundary.strokeWidth"
      :opacity="boundary.opacity"
    />

    <!-- Items layer -->
    <Item v-for="item in scaledItems" :key="item.id"
      :item
      :highlighted="isItemHighlighted(item.id)"
      @hover="handleItemHover"
      @click="(id: number, e: MouseEvent) => emit('itemClick', id, e)"
      @mousemove="(id: number, e: MouseEvent) => emit('itemMousemove', id, e)"
      @mousedown="(id: number, e: MouseEvent) => emit('itemMousedown', id, e)"
      @mouseup="(id: number, e: MouseEvent) => emit('itemMouseup', id, e)"
    />
  </svg>
</template>

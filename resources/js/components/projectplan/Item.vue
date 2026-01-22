<script setup lang="ts">
import { useItem } from './composables/useItem';
import { useTextFitting } from './composables/useTextFitting';
import Polygon from './core/Polygon.vue';

interface ItemProps {
  item: PlanItem;
  highlighted?: boolean;
}

const emit = defineEmits<{
  hover: [id: number, hovering: boolean];
  click: [id: number, event: MouseEvent];
  mousemove: [id: number, event: MouseEvent];
  mousedown: [id: number, event: MouseEvent];
  mouseup: [id: number, event: MouseEvent];
}>();

const { item, highlighted = false } = defineProps<ItemProps>();

const label = computed(() => item.name || item.unit?.identifier || '');

const { style, centroid, textColor, isHovering } = useItem(() => item);
const { fontSize } = useTextFitting(() => item.polygon, label);

const handlePolygonHover = (hovering: boolean) => {
  isHovering.value = hovering;
  emit('hover', item.id, hovering);
};
</script>

<template>
  <g
    class="cursor-pointer"
    :class="{ 'animate-drop': highlighted }"
    @hover="handlePolygonHover"
    @click="(e: MouseEvent) => emit('click', item.id, e)"
    @mousemove="(e: MouseEvent) => emit('mousemove', item.id, e)"
    @mousedown="(e: MouseEvent) => emit('mousedown', item.id, e)"
    @mouseup="(e: MouseEvent) => emit('mouseup', item.id, e)"
  >
    <!-- Polygon base with hover effect -->
    <Polygon
      :polygon="item.polygon"
      :fill="style.fill"
      :stroke="isHovering || highlighted ? '#fbbf24' : style.stroke"
      :stroke-width="highlighted ? style.strokeWidth + 2 : style.strokeWidth"
      :opacity="style.opacity"
      class="transition-all duration-300"
    />

    <!-- Label text, centered on polygon -->
    <text
      v-if="label"
      :x="centroid[0]"
      :y="centroid[1]"
      :fill="textColor"
      :font-size="`${fontSize}px`"
      :title="label"
      text-anchor="middle"
      dominant-baseline="central"
      class="pointer-events-none line-clamp-2 font-semibold transition-all duration-300 select-none"
      >{{ label }}</text
    >
  </g>
</template>

<style scoped>
@keyframes drop-pulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.025);
    opacity: 0.975;
  }
}

.animate-drop {
  animation: drop-pulse 300ms ease-out;
}
</style>

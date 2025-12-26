<script setup lang="ts">
import type { PolygonConfig } from '../types';

const emit = defineEmits<{
  click: [];
  hover: [hovering: boolean];
}>();

const {
  points,
  fill = 'currentColor',
  stroke = 'currentColor',
  strokeWidth = 1,
  opacity = 1,
} = defineProps<PolygonConfig>();

/**
 * Convert point array to SVG polygon points string:
 *   [[x1, y1], [x2, y2], ...] => "x1,y1 x2,y2 ..."
 */
const formatPoints = (points: Point[]) => points.map(([x, y]) => `${x},${y}`).join(' ');
</script>

<template>
  <polygon
    :points="formatPoints(points)"
    :fill="fill"
    :stroke="stroke"
    :stroke-width="strokeWidth"
    :opacity="opacity"
    v-bind="$attrs"
    @click="emit('click')"
    @mouseenter="emit('hover', true)"
    @mouseleave="emit('hover', false)"
  />
</template>

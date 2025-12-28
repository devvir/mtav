<script setup lang="ts">
import type { PolygonConfig } from '../types';

const emit = defineEmits<{
  click: [];
  hover: [hovering: boolean];
  mousedown: [event: MouseEvent];
}>();

const {
  polygon,
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
    :points="formatPoints(polygon)"
    :fill :stroke :stroke-width :opacity
    v-bind="$attrs"
    @click="emit('click')"
    @mouseenter="emit('hover', true)"
    @mouseleave="emit('hover', false)"
    @mousedown="e => emit('mousedown', e)"
  />
</template>

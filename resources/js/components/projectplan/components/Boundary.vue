<script setup lang="ts">
import type { PolygonConfig } from '../types';
import Polygon from '../core/Polygon.vue';

const {
  polygon,
  fill = 'none',
  stroke = '#94a3b8',
  strokeWidth = 2,
  opacity = 1,
} = defineProps<PolygonConfig>();
</script>

<template>
  <Polygon
    :polygon
    :fill :stroke :stroke-width :opacity
    class="pointer-events-none -z-1"
  />
</template>

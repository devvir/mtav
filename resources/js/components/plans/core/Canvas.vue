<script setup lang="ts">
import type { ShapeConfig, BoundaryConfig, CanvasConfig, AutoScale } from '../types';
import Renderer from './Renderer.vue';
import { scale } from '../composables/scaling';

const emit = defineEmits<{
  shapeClick: [id?: string];
  shapeMouseEnter: [id?: string];
  shapeMouseLeave: [id?: string];
}>();

const props = withDefaults(defineProps<{
  shapes: ShapeConfig[];
  config?: CanvasConfig;
  boundary?: BoundaryConfig;
  autoScale?: AutoScale;
}>(), {
  config: () => ({ width: 800, height: 500 }),
  autoScale: 'scale'
});

const scaledResult = computed(
  () => scale(props.autoScale, props.config, props.shapes, props.boundary)
);

const scaledShapes = computed(() => scaledResult.value.shapes);
const scaledBoundary = computed(() => scaledResult.value.boundary);
</script>

<template>
  <Renderer :shapes="scaledShapes" :config :boundary="{
    ...scaledBoundary || {},
    fill: scaledBoundary?.fill || boundary?.fill || '#f8fafc',
    stroke: scaledBoundary?.stroke || boundary?.stroke || '#64748b',
    points: scaledBoundary?.points || boundary?.points || [],
  }"
    @shapeClick="(id?: string) => emit('shapeClick', id)"
    @shapeMouseEnter="(id?: string) => emit('shapeMouseEnter', id)"
    @shapeMouseLeave="(id?: string) => emit('shapeMouseLeave', id)"
  />
</template>

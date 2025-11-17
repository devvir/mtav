<script setup lang="ts">
import type { ShapeConfig } from '../types';

const emit = defineEmits<{
  click: [id: string | undefined];
  mouseenter: [id: string | undefined];
  mouseleave: [id: string | undefined];
}>();

const props = defineProps<{
  config: ShapeConfig;
}>();

const shapeConfig = computed(() => ({
  ...props.config,
  fill: props.config.fill || '#3b82f6',
  stroke: props.config.stroke || '#1e40af',
  strokeWidth: props.config.strokeWidth || 1,
  closed: true,
}));
</script>

<template>
  <v-line
    :config="shapeConfig"
    @click="emit('click', config.id)"
    @mouseenter="emit('mouseenter', config.id)"
    @mouseleave="emit('mouseleave', config.id)"
  />

  <!-- Optional text label -->
  <v-text v-if="config.label" :config="{
    fontSize: 12,
    text: config.label,
    fill: config.stroke || '#1e40af',
    x: Math.min(...config.points.filter((_: number, i: number) => i % 2 === 0)) + 5,
    y: Math.min(...config.points.filter((_: number, i: number) => i % 2 === 1)) + 5,
  }" />
</template>
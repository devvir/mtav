<script setup lang="ts">
import type { ShapeConfig } from './types';
import Canvas from './core/Canvas.vue';

const props = withDefaults(defineProps<{
  shape: ShapeConfig;
  width?: number;
  height?: number;
}>(), { width: 300, height: 200 });

const config = computed(() => ({ width: props.width, height: props.height }));
</script>

<template>
  <Canvas :shapes="[shape]" :config="config" />
</template>
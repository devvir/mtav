<script setup lang="ts">
import { Colors, KEY_COLORS } from '.';

const props = defineProps<{
  idx: number;
  h: number;
  o: number;
  c: number;
  l: number;
}>();

const body = computed(() => ({
  y: Math.min(props.c, props.o),
  h: Math.abs(props.c - props.o),
}));

const line = computed(() => ({
  y1: Math.min(props.l, props.h),
  y2: Math.max(props.l, props.h),
}));

const colors = inject<Colors>(KEY_COLORS) as Colors;
const candleColors = colors[props.c < props.o ? 'red' : 'green'];
</script>

<template>
  <g :title="{ o, h, c, l }" :transform="`translate(${idx * 20})`" width="8">
    <line
      x1="7"
      x2="7"
      v-bind="line"
      stroke-width="2"
      stroke-linecap="round"
      :stroke="candleColors.stroke"
    />
    <rect width="14" v-bind="{ y: body.y, height: body.h }" :fill="candleColors.fill" rx="2" />
  </g>
</template>

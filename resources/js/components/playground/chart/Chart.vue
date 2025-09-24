<script setup lang="ts">
import { Colors, KEY_COLORS, Ohcl } from '.';
import ChartCandle from './ChartCandle.vue';

const props = defineProps<{
  data: Ohcl[];
}>();

// Configuration
const zoomFactor = 1;
const rightGap = 5;

// Container dimensions
const containerW = 1500;
const containerH = 500;

// Data that should fit in the container
const visibleData = props.data.slice(0, Math.round(containerW / 20 / zoomFactor));

// Viewbox coordinates
const viewBoxPadding = 40;
const viewBoxW = 20 * visibleData.length + viewBoxPadding * 2 + rightGap * 20;
const viewBoxH = Math.max(...visibleData.map((d) => d.h)) + viewBoxPadding * 2;

// SVG's height scale factor to fit the container's aspect ratio
const scaleY = (containerH * viewBoxW) / (containerW * viewBoxH);

provide<Colors>(KEY_COLORS, {
  red: { fill: 'hsl(10, 94%, 50%)', stroke: 'hsl(10, 100%, 35%)' },
  green: { fill: 'hsl(120, 80%, 60%)', stroke: 'hsl(120, 80%, 40%)' },
});
</script>

<template>
  <div
    class="flex items-center justify-center overflow-hidden rounded-2xl bg-zinc-900"
    :class="`w-[${containerW}px] h-[${containerH}px]`"
  >
    <svg
      class="mx-2 my-1 h-[500px] w-[1500px] rounded-lg border-1 border-white/20 bg-stone-900"
      :style="`transform: scaleY(-${scaleY});`"
      :viewBox="`-${viewBoxPadding} -${viewBoxPadding} ${viewBoxW} ${viewBoxH}`"
    >
      <!-- ChartCanvas : scales -->
      <!-- ChartOverlay : tools, indicators, etc. -->
      <ChartCandle v-for="(ohcl, i) in visibleData" :key="i" :idx="i" v-bind="ohcl" />
    </svg>
  </div>
</template>

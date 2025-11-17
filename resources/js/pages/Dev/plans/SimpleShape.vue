<script setup lang="ts">
import { getCSSVar } from '@/components/plans';
import Canvas from '@/components/plans/core/Canvas.vue';
import type { ShapeConfig, AutoScale } from '@/components/plans';
import { Card, CardContent, CardHeader } from '@/components/card';

// Example shape data - 100x100 square
const exampleShape: ShapeConfig = {
  points: [0, 0, 100, 0, 100, 100, 0, 100], // 100x100 square
  fill: '#10b981', // bright emerald green - visible in dark mode
  stroke: '#059669', // darker emerald stroke
  strokeWidth: 2,
  id: 'example-square',
};

// Container dimensions
const wideContainer = { width: 200, height: 150 };
const tallContainer = { width: 150, height: 200 };
const squareContainer = { width: 200, height: 200 };

// Custom boundary for better visibility in dev tests
const createBoundary = (width: number, height: number) => ({
  points: [0, 0, width, 0, width, height, 0, height],
  stroke: '#f59e0b', // bright amber - very visible
  strokeWidth: 3,    // thick stroke
  fill: 'transparent'
});

// Auto scale modes
const scaleOff: AutoScale = 'off';
const scaleCenter: AutoScale = 'center';
const scaleProportional: AutoScale = 'scale';
const scaleStretch: AutoScale = 'stretch';

// Debug logging
console.log('Shape data:', exampleShape);
console.log('CSS Fill color:', getCSSVar('--primary'));
console.log('CSS Stroke color:', getCSSVar('--primary-foreground'));
</script>

<template>
  <Card class="max-w-full">
    <CardHeader title="Shape Scaling Test">
      Testing scaling behaviors across different container dimensions and modes
    </CardHeader>

    <CardContent>
      <div class="grid grid-cols-4 gap-4 p-4 mx-auto place-items-center [&>*]:w-full [&>*]:text-center">
        <!-- Row 1: Tall containers -->
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Original Size</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, original position</p>
          <Canvas :shapes="[exampleShape]" :config="tallContainer" :boundary="createBoundary(150, 200)" :autoScale="scaleOff" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Centered</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, centered</p>
          <Canvas :shapes="[exampleShape]" :config="tallContainer" :boundary="createBoundary(150, 200)" :autoScale="scaleCenter" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Proportional</h3>
          <p class="text-xs p-2 text-muted-foreground">Scaled to fit, aspect preserved</p>
          <Canvas :shapes="[exampleShape]" :config="tallContainer" :boundary="createBoundary(150, 200)" :autoScale="scaleProportional" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Stretched</h3>
          <p class="text-xs p-2 text-muted-foreground">Distorted to fill container</p>
          <Canvas :shapes="[exampleShape]" :config="tallContainer" :boundary="createBoundary(150, 200)" :autoScale="scaleStretch" />
        </div>

        <!-- Row 2: Wide containers -->
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Original Size</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, original position</p>
          <Canvas :shapes="[exampleShape]" :config="wideContainer" :boundary="createBoundary(200, 150)" :autoScale="scaleOff" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Centered</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, centered</p>
          <Canvas :shapes="[exampleShape]" :config="wideContainer" :boundary="createBoundary(200, 150)" :autoScale="scaleCenter" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Proportional</h3>
          <p class="text-xs p-2 text-muted-foreground">Scaled to fit, aspect preserved</p>
          <Canvas :shapes="[exampleShape]" :config="wideContainer" :boundary="createBoundary(200, 150)" :autoScale="scaleProportional" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Stretched</h3>
          <p class="text-xs p-2 text-muted-foreground">Distorted to fill container</p>
          <Canvas :shapes="[exampleShape]" :config="wideContainer" :boundary="createBoundary(200, 150)" :autoScale="scaleStretch" />
        </div>

        <!-- Row 3: Square containers -->
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Original Size</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, original position</p>
          <Canvas :shapes="[exampleShape]" :config="squareContainer" :boundary="createBoundary(200, 200)" :autoScale="scaleOff" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Centered</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, centered</p>
          <Canvas :shapes="[exampleShape]" :config="squareContainer" :boundary="createBoundary(200, 200)" :autoScale="scaleCenter" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Proportional</h3>
          <p class="text-xs p-2 text-muted-foreground">Scaled to fit, aspect preserved</p>
          <Canvas :shapes="[exampleShape]" :config="squareContainer" :boundary="createBoundary(200, 200)" :autoScale="scaleProportional" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Stretched</h3>
          <p class="text-xs p-2 text-muted-foreground">Distorted to fill container</p>
          <Canvas :shapes="[exampleShape]" :config="squareContainer" :boundary="createBoundary(200, 200)" :autoScale="scaleStretch" />
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import Canvas from '@/components/projectplan/core/Canvas.vue';
import type { AutoScale } from '@/components/projectplan';
import { Card, CardContent, CardHeader } from '@/components/card';

// Example PlanItem data - 100x100 square
const exampleItem: PlanItem = {
  id: 1,
  type: 'unit',
  polygon: [[0, 0], [100, 0], [100, 100], [0, 100]], // 100x100 square
  floor: 0,
  name: 'Unit A1',
  metadata: {
    fill: '#10b981',
    stroke: '#059669',
    strokeWidth: 2,
  },
  is_deleted: false,
  created_at: '2024-01-01T00:00:00Z',
  created_ago: '1 day ago',
  deleted_at: null,
  plan: { id: 1 },
};

// Container dimensions
const wideContainer = { width: 200, height: 150 };
const tallContainer = { width: 150, height: 200 };
const squareContainer = { width: 200, height: 200 };

// Custom boundary for better visibility in dev tests
const createBoundary = (width: number, height: number) => ({
  points: [[0, 0], [width, 0], [width, height], [0, height]],
  stroke: '#f59e0b', // bright amber - very visible
  strokeWidth: 3,    // thick stroke
  fill: 'transparent'
});

// Auto scale modes
const scaleContain: AutoScale = 'contain';
const scaleCover: AutoScale = 'cover';
const scaleFill: AutoScale = 'fill';
</script>

<template>
  <Card class="max-w-full">
    <CardHeader title="Shape Scaling Test">
      Testing scaling behaviors across different container dimensions and modes
    </CardHeader>

    <CardContent>
      <div class="grid grid-cols-4 gap-4 p-4 mx-auto place-items-center *:w-full *:text-center">
        <!-- Row 1: Tall containers -->
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Original Size</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, original position</p>
          <Canvas :items="[exampleItem]" :config="tallContainer" :boundary="createBoundary(150, 200)" :autoScale="scaleContain" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Centered</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, centered</p>
          <Canvas :items="[exampleItem]" :config="tallContainer" :boundary="createBoundary(150, 200)" :autoScale="scaleContain" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Proportional</h3>
          <p class="text-xs p-2 text-muted-foreground">Scaled to fit, aspect preserved</p>
          <Canvas :items="[exampleItem]" :config="tallContainer" :boundary="createBoundary(150, 200)" :autoScale="scaleCover" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Stretched</h3>
          <p class="text-xs p-2 text-muted-foreground">Distorted to fill container</p>
          <Canvas :items="[exampleItem]" :config="tallContainer" :boundary="createBoundary(150, 200)" :autoScale="scaleFill" />
        </div>

        <!-- Row 2: Wide containers -->
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Original Size</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, original position</p>
          <Canvas :items="[exampleItem]" :config="wideContainer" :boundary="createBoundary(200, 150)" :autoScale="scaleContain" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Centered</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, centered</p>
          <Canvas :items="[exampleItem]" :config="wideContainer" :boundary="createBoundary(200, 150)" :autoScale="scaleContain" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Proportional</h3>
          <p class="text-xs p-2 text-muted-foreground">Scaled to fit, aspect preserved</p>
          <Canvas :items="[exampleItem]" :config="wideContainer" :boundary="createBoundary(200, 150)" :autoScale="scaleCover" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Stretched</h3>
          <p class="text-xs p-2 text-muted-foreground">Distorted to fill container</p>
          <Canvas :items="[exampleItem]" :config="wideContainer" :boundary="createBoundary(200, 150)" :autoScale="scaleFill" />
        </div>

        <!-- Row 3: Square containers -->
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Original Size</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, original position</p>
          <Canvas :items="[exampleItem]" :config="squareContainer" :boundary="createBoundary(200, 200)" :autoScale="scaleContain" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Centered</h3>
          <p class="text-xs p-2 text-muted-foreground">No scaling, centered</p>
          <Canvas :items="[exampleItem]" :config="squareContainer" :boundary="createBoundary(200, 200)" :autoScale="scaleContain" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Proportional</h3>
          <p class="text-xs p-2 text-muted-foreground">Scaled to fit, aspect preserved</p>
          <Canvas :items="[exampleItem]" :config="squareContainer" :boundary="createBoundary(200, 200)" :autoScale="scaleCover" />
        </div>
        <div class="border border-border">
          <h3 class="text-base font-semibold p-2 bg-muted">Stretched</h3>
          <p class="text-xs p-2 text-muted-foreground">Distorted to fill container</p>
          <Canvas :items="[exampleItem]" :config="squareContainer" :boundary="createBoundary(200, 200)" :autoScale="scaleFill" />
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/card';
import type { PolygonConfig, ScaleMode } from '@/components/projectplan';
import Canvas from '@/components/projectplan/core/Canvas.vue';

// Example PlanItem - single square for testing scaling
const createTestItems = (): PlanItem[] => [
  {
    id: 1,
    type: 'unit',
    polygon: [
      [100, 100],
      [300, 100],
      [300, 300],
      [100, 300],
    ],
    floor: 0,
    name: 'A1',
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
  },
];

// Create boundary matching the shape's bounding area
const createTestBoundary = (): PolygonConfig => ({
  polygon: [
    [50, 50],
    [350, 50],
    [350, 350],
    [50, 350],
  ],
  stroke: '#f59e0b',
  strokeWidth: 3,
  fill: 'none',
});

// Different container dimensions
const containers = [
  { name: 'Tall', ratio: 3 / 4 },
  { name: 'Wide', ratio: 4 / 3 },
  { name: 'Square', ratio: 1 },
];

const scaleModes: ScaleMode[] = ['contain', 'cover', 'fill', 'none'];
const modeLabels: Record<ScaleMode, string> = {
  contain: 'Contain',
  cover: 'Cover',
  fill: 'Fill',
  none: 'None',
};

const modeDescriptions: Record<ScaleMode, string> = {
  contain: 'Letterbox (fit with ratio)',
  cover: 'Cover (fill with ratio)',
  fill: 'Fill (distort ratio)',
  none: 'No scaling (original size)',
};
</script>

<template>
  <Card class="max-w-full">
    <CardHeader title="Scaling Modes Grid">
      Testing all scaling behaviors across different container dimensions
    </CardHeader>

    <CardContent>
      <div class="w-full space-y-6">
        <!-- Grid of scaling mode tests -->
        <div class="space-y-8">
          <div v-for="container in containers" :key="container.name" class="space-y-3">
            <h3 class="text-sm font-semibold">
              {{ container.name }} (Ratio 12/{{ Math.round(12 * container.ratio) }})
            </h3>
            <div class="grid w-full grid-cols-4 gap-4">
              <div
                v-for="scaleMode in scaleModes"
                :key="scaleMode"
                class="flex flex-col overflow-hidden rounded-lg border border-border"
              >
                <div class="bg-muted p-2 text-center">
                  <h4 class="text-sm font-semibold">{{ modeLabels[scaleMode] }}</h4>
                </div>
                <div
                  class="flex min-h-[250px] flex-1 items-center justify-center border border-dashed border-gray-300 bg-white/90 p-2"
                >
                  <Canvas
                    :items="createTestItems()"
                    :boundary="createTestBoundary()"
                    :scaleMode
                    :forceRatio="container.ratio"
                    bgColor="#fff"
                  />
                </div>
                <div class="bg-muted p-2 text-center text-xs text-muted-foreground">
                  {{ modeDescriptions[scaleMode] }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

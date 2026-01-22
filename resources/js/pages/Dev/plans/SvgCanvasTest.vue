<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/card';
import type { PolygonConfig } from '@/components/projectplan';
import Canvas from '@/components/projectplan/core/Canvas.vue';

// Example PlanItems (housing units)
const items = computed<PlanItem[]>(() => [
  {
    id: 1,
    type: 'unit',
    polygon: [
      [50, 50],
      [250, 50],
      [250, 150],
      [50, 150],
    ], // Large rectangle
    floor: 1,
    name: 'A-101',
    metadata: {
      fill: '#e0f2fe',
      stroke: '#0284c7',
      strokeWidth: 2,
    },
    is_deleted: false,
    created_at: '2024-01-01T00:00:00Z',
    created_ago: '1 day ago',
    deleted_at: null,
    plan: { id: 1 },
  },
  {
    id: 2,
    type: 'unit',
    polygon: [
      [300, 50],
      [500, 50],
      [500, 150],
      [300, 150],
    ], // Large rectangle
    floor: 1,
    name: 'A-102',
    metadata: {
      fill: '#dbeafe',
      stroke: '#0284c7',
      strokeWidth: 2,
    },
    is_deleted: false,
    created_at: '2024-01-01T00:00:00Z',
    created_ago: '1 day ago',
    deleted_at: null,
    plan: { id: 1 },
  },
  {
    id: 3,
    type: 'unit',
    polygon: [
      [50, 200],
      [250, 200],
      [250, 300],
      [50, 300],
    ], // Wide rectangle
    floor: 1,
    name: 'A-103',
    metadata: {
      fill: '#bfdbfe',
      stroke: '#0284c7',
      strokeWidth: 2,
    },
    is_deleted: false,
    created_at: '2024-01-01T00:00:00Z',
    created_ago: '1 day ago',
    deleted_at: null,
    plan: { id: 1 },
  },
  {
    id: 4,
    type: 'park',
    polygon: [
      [300, 200],
      [450, 180],
      [480, 300],
      [350, 320],
    ], // Irregular polygon
    floor: 0,
    name: 'Park',
    metadata: {
      fill: '#f0fdf4',
      stroke: '#16a34a',
      strokeWidth: 2,
    },
    is_deleted: false,
    created_at: '2024-01-01T00:00:00Z',
    created_ago: '1 day ago',
    deleted_at: null,
    plan: { id: 1 },
  },
]);

// Boundary polygon (project outline)
const boundary = computed<PolygonConfig>(() => ({
  polygon: [
    [20, 20],
    [520, 20],
    [520, 340],
    [20, 340],
  ],
  stroke: '#64748b',
  strokeWidth: 3,
  fill: 'none',
  opacity: 1,
}));
</script>

<template>
  <Card class="max-w-full">
    <CardHeader title="New SVG Canvas Test">
      Testing the new SVG-based floor plan library
    </CardHeader>

    <CardContent class="p-6">
      <div class="mx-auto w-1/2 space-y-6">
        <!-- Canvas -->
        <div class="rounded-lg border border-border bg-card p-4">
          <h3 class="mb-4 font-medium">Responsive Floor Plan (Scale Mode)</h3>
          <Canvas :items :boundary :forceRatio="1" scaleMode="contain" />
        </div>

        <!-- Info -->
        <div class="mx-auto grid grid-cols-3 gap-4 text-sm">
          <div class="rounded bg-muted p-3">
            <div class="font-semibold">Shapes</div>
            <div class="text-muted-foreground">{{ items.length }} units/areas</div>
          </div>
          <div class="rounded bg-muted p-3">
            <div class="font-semibold">Boundary</div>
            <div class="text-muted-foreground">{{ boundary.polygon.length / 2 }} corners</div>
          </div>
          <div class="rounded bg-muted p-3">
            <div class="font-semibold">Scale Mode</div>
            <div class="text-muted-foreground">contain</div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Unit } from '@/components/plans';

// Example Unit data with plan_item
const exampleUnit: Unit & { plan_item: PlanItem } = {
  id: 1,
  identifier: 'A-101',
  created_at: '2024-01-01T00:00:00Z',
  created_ago: '1 year ago',
  deleted_at: null,

  project: { id: 1 },
  type: { id: 1 },
  family: { id: 1 },

  plan_item: {
    id: 1,
    type: 'unit',
    polygon: [50, 50, 150, 50, 150, 120, 50, 120], // Rectangle: 100x70
    floor: 1,
    name: 'Unit A-101',
    metadata: null,
    created_at: '2024-01-01T00:00:00Z',
    created_ago: '1 year ago',
    deleted_at: null,
    plan: { id: 1 },
  }
};

// Example styling configurations
const styledConfig = {
  fill: '#10b981', // green
  stroke: '#065f46', // dark green
  strokeWidth: 2,
  opacity: 0.8,
};

const highlightedConfig = {
  fill: '#f59e0b', // amber
  stroke: '#92400e', // dark amber
  strokeWidth: 3,
  highlighted: true,
};
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="space-y-2">
      <h1 class="text-2xl font-semibold">Single Unit Test</h1>
      <p class="text-muted-foreground">
        Testing the Unit component with different styling configurations
      </p>
    </div>

    <div class="grid gap-6">
      <!-- Default styling -->
      <div class="border rounded-lg p-4 bg-card">
        <h2 class="text-lg font-medium mb-4">Default Unit Styling</h2>
        <Unit :unit="exampleUnit" :width="250" :height="200" />
      </div>

      <!-- Custom styling -->
      <div class="border rounded-lg p-4 bg-card">
        <h2 class="text-lg font-medium mb-4">Custom Styled Unit</h2>
        <Unit :unit="exampleUnit" :config="styledConfig" :width="250" :height="200" />
      </div>

      <!-- Highlighted unit -->
      <div class="border rounded-lg p-4 bg-card">
        <h2 class="text-lg font-medium mb-4">Highlighted Unit</h2>
        <Unit :unit="exampleUnit" :config="highlightedConfig" :width="250" :height="200" />
      </div>
    </div>
  </div>
</template>
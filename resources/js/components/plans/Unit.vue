<script setup lang="ts">
import type { ShapeConfig } from './types';
import Shape from './Shape.vue';

interface UnitStyleConfig {
  fill?: string;
  stroke?: string;
  strokeWidth?: number;
  opacity?: number;
  highlighted?: boolean;
}

interface Props {
  unit: Unit & { plan_item?: PlanItem };
  config?: UnitStyleConfig;
  width?: number;
  height?: number;
}

const props = withDefaults(defineProps<Props>(), {
  config: () => ({}),
  width: 300,
  height: 200,
});

// Convert Unit + PlanItem to ShapeConfig
const shapeConfig = computed((): ShapeConfig => {
  const planItem = props.unit.plan_item;

  if (!planItem) {
    throw new Error('Unit must have plan_item data to be rendered');
  }

  return {
    id: `unit-${props.unit.id}`,
    points: planItem.polygon,
    fill: props.config.fill || '#3b82f6',
    stroke: props.config.stroke || '#1e40af',
    strokeWidth: props.config.strokeWidth || 1,
    opacity: props.config.opacity || 1,
    label: props.unit.identifier || `Unit ${props.unit.id}`,
    floor: planItem.floor,
    highlighted: props.config.highlighted || false,
  };
});
</script>

<template>
  <Shape
    :shape="shapeConfig"
    :width="width"
    :height="height"
  />
</template>
<script setup lang="ts">
import type { ShapeConfig, BoundaryConfig, CanvasConfig } from './types';
import Canvas from './core/Canvas.vue';

interface Props {
  plan: Plan;
  highlightUnitId?: number;
  colorOptions?: {
    assignedUnits?: string;
    availableUnits?: string;
    highlighted?: string;
    boundaryStroke?: string;
    boundaryFill?: string;
  };
}

interface Emits {
  unitClick: [unitId: number];
  unitHover: [unitId: number | null];
}

const props = withDefaults(defineProps<Props>(), {
  colorOptions: () => ({
    assignedUnits: '#dcfce7',
    availableUnits: '#e0f2fe',
    highlighted: '#fbbf24',
    boundaryStroke: '#6b7280',
    boundaryFill: 'transparent',
  }),
});

const emit = defineEmits<Emits>();

// Business logic: Convert plan items to shape configurations
const shapes = computed((): ShapeConfig[] => {
  return props.plan.items.map((planItem: PlanItem) => {
    const isUnit = planItem.type === 'unit';
    const isHighlighted = isUnit && props.highlightUnitId === planItem.unit?.id;

    let fill = '#ffffff';
    let stroke = '#000000';
    let label: string | undefined;
    let id: string | undefined;

    if (isUnit && planItem.unit) {
      // BUSINESS RULE: Unit is assigned if it has a family
      const isAssigned = !!planItem.unit.family;
      fill = isAssigned
        ? props.colorOptions?.assignedUnits || '#dcfce7'
        : props.colorOptions?.availableUnits || '#e0f2fe';
      stroke = isAssigned ? '#22c55e' : '#3b82f6';
      label = planItem.unit.identifier || undefined;
      id = `unit-${planItem.unit.id}`;
    } else {
      // Common areas with type-specific colors
      const colorMap: Record<string, string> = {
        park: '#f0fdf4',
        street: '#f1f5f9',
        common: '#fefce8',
        amenity: '#fef3f2',
      };
      fill = colorMap[planItem.type] || '#ffffff';
      label = planItem.name || undefined;
      id = `area-${planItem.id}`;
    }

    if (isHighlighted) {
      stroke = props.colorOptions?.highlighted || '#fbbf24';
    }

    return {
      points: planItem.polygon,
      floor: planItem.floor,
      label,
      fill,
      stroke,
      highlighted: isHighlighted,
      id,
    };
  });
});

const boundary = computed((): BoundaryConfig | undefined => {
  if (!props.plan.polygon || props.plan.polygon.length === 0) {
    return undefined;
  }

  return {
    points: props.plan.polygon,
    stroke: props.colorOptions?.boundaryStroke || '#6b7280',
    fill: props.colorOptions?.boundaryFill || 'transparent',
    dash: [10, 5],
  };
});

const canvasConfig = computed((): CanvasConfig => ({
  width: 800,
  height: 600,
}));

const handleShapeClick = (id?: string) => {
  console.log(id);
  if (id?.startsWith('unit-')) {
    const unitId = parseInt(id.replace('unit-', ''));
    if (!isNaN(unitId)) emit('unitClick', unitId);
  }
};

const handleShapeMouseEnter = (id?: string) => {
  if (id?.startsWith('unit-')) {
    const unitId = parseInt(id.replace('unit-', ''));
    if (!isNaN(unitId)) emit('unitHover', unitId);
  }
};

const handleShapeMouseLeave = () => emit('unitHover', null);
</script>

<template>
  <Canvas
    :shapes
    :boundary
    :config="canvasConfig"
    autoScale="scale"
    @shape-click="handleShapeClick"
    @shape-mouse-enter="handleShapeMouseEnter"
    @shape-mouse-leave="handleShapeMouseLeave"
  />
</template>
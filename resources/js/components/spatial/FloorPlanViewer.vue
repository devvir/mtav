<script setup lang="ts">
import Konva from 'konva';

const props = defineProps<{
  planData: {
    id: number;
    name: string;
    dimensions: { width: number; height: number };
    units: Array<{
      id: string;
      points: number[];
      type: string;
      assigned: boolean;
    }>;
    unitTypes?: Array<{
      name: string;
      color: string;
      description: string;
    }>;
    buildings?: Array<{
      name: string;
      points: number[];
      type: string;
    }>;
    commonAreas?: Array<{
      name: string;
      points: number[];
      type: string;
    }>;
    circulation?: Array<{
      name: string;
      points: number[];
      type: string;
    }>;
  };
  highlightUnitId?: string;
}>();

const containerRef = ref<HTMLDivElement>();
let stage: Konva.Stage;
let layer: Konva.Layer;

onMounted(() => {
  if (!containerRef.value) return;

  // Create stage and layer
  stage = new Konva.Stage({
    container: containerRef.value,
    width: Math.min(800, containerRef.value.clientWidth),
    height: 500,
  });

  layer = new Konva.Layer();
  stage.add(layer);

  // Draw buildings (background)
  if (props.planData.buildings) {
    props.planData.buildings.forEach((building: { name: string; points: number[]; type: string }) => {
      const buildingPolygon = new Konva.Line({
        points: building.points,
        fill: 'transparent',
        stroke: '#e2e8f0',
        strokeWidth: 2,
        dash: [5, 5],
        closed: true,
      });

      const buildingBounds = getBounds(building.points);
      const buildingLabel = new Konva.Text({
        x: buildingBounds.x + 5,
        y: buildingBounds.y + 5,
        text: building.name,
        fontSize: 12,
        fontFamily: 'Inter, sans-serif',
        fill: '#64748b',
      });

      layer.add(buildingPolygon);
      layer.add(buildingLabel);
    });
  }

  // Draw units
  props.planData.units.forEach((unit: { id: string; points: number[]; type: string; assigned: boolean }) => {
    const isHighlighted = props.highlightUnitId === unit.id;

    // Create polygon from points array
    const unitPolygon = new Konva.Line({
      points: unit.points,
      fill: getUnitColor(unit, isHighlighted),
      stroke: isHighlighted ? '#f59e0b' : '#94a3b8',
      strokeWidth: isHighlighted ? 3 : 1,
      closed: true,
    });

    // Calculate center point for label positioning
    const bounds = getBounds(unit.points);
    const centerX = bounds.x + bounds.width / 2;
    const centerY = bounds.y + bounds.height / 2;

    const unitLabel = new Konva.Text({
      x: centerX,
      y: centerY - 5, // Offset slightly up from center
      text: unit.id,
      fontSize: 10,
      fontFamily: 'Inter, sans-serif',
      fill: '#374151',
      align: 'center',
      verticalAlign: 'middle',
      fontStyle: isHighlighted ? 'bold' : 'normal',
      offsetX: 15, // Center the text horizontally
    });

    // Add hover effects
    unitPolygon.on('mouseenter', () => {
      unitPolygon.stroke('#3b82f6');
      unitPolygon.strokeWidth(2);
      stage.container().style.cursor = 'pointer';
      layer.draw();
    });

    unitPolygon.on('mouseleave', () => {
      unitPolygon.stroke(isHighlighted ? '#f59e0b' : '#94a3b8');
      unitPolygon.strokeWidth(isHighlighted ? 3 : 1);
      stage.container().style.cursor = 'default';
      layer.draw();
    });

    // Add click handler for potential future interactions
    unitPolygon.on('click', () => {
      console.log(`Clicked unit: ${unit.id}`, unit);
    });

    layer.add(unitPolygon);
    layer.add(unitLabel);
  });

  layer.draw();
});

function getUnitColor(unit: { type: string; assigned: boolean }, isHighlighted: boolean): string {
  // Find the color for this unit type
  const unitTypeData = props.planData.unitTypes?.find((ut: { name: string; color: string; description: string }) => ut.name === unit.type);

  if (isHighlighted) {
    return unitTypeData?.color ? lightenColor(unitTypeData.color) : '#fef3c7';
  }

  return unitTypeData?.color || '#e0f2fe';
}

// Helper function to lighten a hex color for highlighting
function lightenColor(color: string): string {
  // Simple lightening by adding white opacity
  const hex = color.replace('#', '');
  const r = parseInt(hex.substr(0, 2), 16);
  const g = parseInt(hex.substr(2, 2), 16);
  const b = parseInt(hex.substr(4, 2), 16);

  // Lighten by 30%
  const lightR = Math.min(255, Math.floor(r + (255 - r) * 0.3));
  const lightG = Math.min(255, Math.floor(g + (255 - g) * 0.3));
  const lightB = Math.min(255, Math.floor(b + (255 - b) * 0.3));

  return `#${lightR.toString(16).padStart(2, '0')}${lightG.toString(16).padStart(2, '0')}${lightB.toString(16).padStart(2, '0')}`;
}

// Helper function to calculate bounding box from points array
function getBounds(points: number[]) {
  let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

  for (let i = 0; i < points.length; i += 2) {
    const x = points[i];
    const y = points[i + 1];
    if (x < minX) minX = x;
    if (x > maxX) maxX = x;
    if (y < minY) minY = y;
    if (y > maxY) maxY = y;
  }

  return {
    x: minX,
    y: minY,
    width: maxX - minX,
    height: maxY - minY
  };
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold">{{ planData.name }} - Floor Plan</h3>
      <div class="flex gap-4 text-sm">
        <div class="flex items-center gap-2">
          <div class="w-4 h-4 bg-green-200 border border-green-300 rounded"></div>
          <span>Assigned</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-4 h-4 bg-blue-200 border border-blue-300 rounded"></div>
          <span>Available</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-4 h-4 bg-amber-200 border-2 border-amber-500 rounded"></div>
          <span>Highlighted</span>
        </div>
      </div>
    </div>

    <div
      ref="containerRef"
      class="border border-border rounded-lg bg-white overflow-hidden"
      style="max-width: 100%;"
    />

    <div class="text-sm text-text-muted">
      <p><strong>Controls:</strong> Hover over units to highlight them, click for details</p>
      <p><strong>Stats:</strong> {{ planData.units.length }} units total,
        {{ planData.units.filter((u: { assigned: boolean }) => u.assigned).length }} assigned,
        {{ planData.units.filter((u: { assigned: boolean }) => !u.assigned).length }} available
      </p>
    </div>
  </div>
</template>
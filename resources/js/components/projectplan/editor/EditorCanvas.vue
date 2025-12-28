<script setup lang="ts">
import Canvas from '../core/Canvas.vue';
import type { PolygonConfig } from '../types';
import { usePlanEditor } from '../composables/usePlanEditor';

interface Props {
  plan: ApiResource<Plan>;
  items: PlanItem[];
}

const emit = defineEmits<{
  'update:items': [items: PlanItem[]];
  'item-moved': [itemId: number, newPolygon: Point[]];
}>();

const { plan, items } = defineProps<Props>();

const boundary = computed<PolygonConfig>(() => ({
  polygon: plan.polygon,
  stroke: 'hsl(var(--border-strong))',
  strokeWidth: 2,
  fill: 'transparent',
}));

// Drag state
const draggedItemId = ref<number | null>(null);
const ghostPosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const justDroppedItemId = ref<number | null>(null);

const draggedItem = computed(() =>
  items.find((item: PlanItem) => item.id === draggedItemId.value)
);

const containerRef = useTemplateRef<HTMLDivElement>('canvasContainer');

// Coordinate conversion and polygon translation
const {
  ghostDimensions,
  screenToCanvasCoords,
  translateItemTo,
} = usePlanEditor(containerRef, draggedItem, boundary);

const handleMouseMove = (event: MouseEvent) => {
  if (isDragging.value && draggedItemId.value !== null) {
    ghostPosition.value = { x: event.clientX, y: event.clientY };
  }
};

const handleItemMouseDown = (id: number, event: MouseEvent) => {
  // Only start dragging on left click
  if (event.button !== 0) return;

  draggedItemId.value = id;
  isDragging.value = true;
  ghostPosition.value = { x: event.clientX, y: event.clientY };
};

const handleMouseUp = () => {
  if (isDragging.value && draggedItemId.value !== null && draggedItem.value) {
    // Convert ghost position to canvas coordinates
    const [canvasX, canvasY] = screenToCanvasCoords(ghostPosition.value.x, ghostPosition.value.y);

    // Update item's polygon to new position (snapping handled in translateItemTo)
    const itemIndex = items.findIndex((item: PlanItem) => item.id === draggedItemId.value);
    if (itemIndex !== -1) {
      const gridSize = 5;
      const newPolygon = translateItemTo(draggedItem.value, canvasX, canvasY, gridSize);

      // Emit the change to parent
      emit('item-moved', draggedItemId.value, newPolygon);

      // Trigger animation
      justDroppedItemId.value = draggedItemId.value;
      setTimeout(() => {
        justDroppedItemId.value = null;
      }, 300);
    }

    // Clear drag state
    isDragging.value = false;
    draggedItemId.value = null;
  }
};
</script>

<template>
  <div class="flex-1 flex flex-col relative">
    <!-- Canvas Container -->
    <div
      ref="canvasContainer"
      class="flex-1 overflow-auto relative"
      tabindex="0"
      @mouseup="handleMouseUp"
      @mousemove="handleMouseMove"
    >
      <Canvas :items :boundary
        :highlighted-item-id="justDroppedItemId"
        class="size-full"
        @item-mousedown="handleItemMouseDown"
      />
    </div>

    <!-- Ghost Item - outside overflow container so it's not clipped -->
    <div v-if="isDragging && draggedItem"
      class="fixed pointer-events-none z-50 -translate-1/2 opacity-70"
      :style="{
        left: `${ghostPosition.x}px`,
        top: `${ghostPosition.y}px`,
        width: `${ghostDimensions.width}px`,
        height: `${ghostDimensions.height}px`
      }"
    >
      <Canvas :items="[draggedItem]" auto-scale="contain" />
    </div>
  </div>
</template>

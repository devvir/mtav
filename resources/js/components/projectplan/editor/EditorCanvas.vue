<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import Canvas from '../core/Canvas.vue';
import type { PolygonConfig } from '../types';
import { Button } from '@/components/ui/button';
import { usePlanEditor } from '../composables/usePlanEditor';

interface Props {
  plan: ApiResource<Plan>;
}

const { plan } = defineProps<Props>();

const items = ref(plan.items);
const hasChanges = ref(false);
const processing = ref(false);

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
  items.value.find((item: PlanItem) => item.id === draggedItemId.value)
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
    const itemIndex = items.value.findIndex((item: PlanItem) => item.id === draggedItemId.value);
    if (itemIndex !== -1) {
      const gridSize = 5;
      const newPolygon = translateItemTo(draggedItem.value, canvasX, canvasY, gridSize);
      items.value[itemIndex] = { ...items.value[itemIndex], polygon: newPolygon };
      hasChanges.value = true;

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

const resetAllChanges = () => {
  items.value = plan.items;
  hasChanges.value = false;
};

const saveChanges = () => {
  const payload = {
    polygon: plan.polygon,
    items: items.value.map((item: PlanItem) => ({
      id: item.id,
      polygon: item.polygon,
    })),
  };

  processing.value = true;

  router.patch(route('plans.update', plan.id), payload, {
    onSuccess: () => {
      // Reset dirty state on successful save
      hasChanges.value = false;
      processing.value = false;
    },
    onError: () => {
      // Re-enable button on error
      processing.value = false;
    },
  });
};
</script>

<template>
  <div class="flex h-full bg-background justify-center">
    <!-- Main Canvas Area -->
    <div class="flex-1 flex flex-col overflow-hidden max-w-5xl">
      <!-- Header -->
      <div class="border-b border-border bg-card px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-xl font-bold text-foreground">
              {{ `${_('Project Plan')} "${plan.project.name}"` }}
            </h1>
          </div>

          <div class="flex gap-2">
            <Button
              variant="outline"
              @click="resetAllChanges"
              :disabled="!hasChanges || processing"
            >
              {{ _('Reset All Changes') }}
            </Button>

            <Button
              @click="saveChanges"
              :disabled="!hasChanges || processing"
              class="overflow-hidden grid"
            >
              <div class="row-start-1 col-start-1 transition-opacity" :class="{ 'invisible': !processing }">{{ _('Saving...') }}</div>
              <div class="row-start-1 col-start-1 transition-opacity" :class="{ 'invisible': processing || !hasChanges }">{{ _('Save Changes') }}</div>
              <div class="row-start-1 col-start-1 transition-opacity" :class="{ 'invisible': processing || hasChanges }">{{ _('No Changes') }}</div>
            </Button>
          </div>
        </div>
      </div>

      <!-- Canvas -->
      <div
        ref="canvasContainer"
        class="flex-1 overflow-auto relative mt-4"
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

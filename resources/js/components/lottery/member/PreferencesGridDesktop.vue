<script setup lang="ts">
import { useDragAndDrop } from '@/composables/useDragAndDrop';
import PreferencesControls from './PreferencesControls.vue';

const emit = defineEmits<{
  change: [updatedPreferences: Unit[]];
}>();

const props = defineProps<{
  preferences: Unit[];
  disabled: boolean;
}>();

// UI helpers
const getUnitRotation = (unitId: number) => {
  // Pseudo-random rotation using sine-based hash (seeded by unit ID)
  const x = Math.sin(unitId * 12.9898 + 78.233) * 43758.5453;
  const random = x - Math.floor(x);
  return (random - 0.5) * 4; // Range: -2deg to +2deg
};

// Local reactive copy for reordering
const localPreferences = reactive([...props.preferences]);

// Watch for external changes
watch(
  () => props.preferences,
  (newPreferences: Unit[]) => {
    localPreferences.splice(0, localPreferences.length, ...newPreferences);
  },
  { deep: true },
);

// Preference reordering logic
const move = (from: number, to: number) => {
  localPreferences.splice(to, 0, localPreferences.splice(from, 1)[0]);
  emit('change', [...localPreferences]);
};

// Drag and drop
const { draggedIndex, handleDragStart, handleDrop, handleDragEnd } = useDragAndDrop({
  onMove: move,
});

// Keyboard accessibility
const moveUp = (index: number) => {
  if (index > 0) move(index, index - 1);
};

const moveDown = (index: number) => {
  if (index < localPreferences.length - 1) move(index, index + 1);
};
</script>

<template>
  <TransitionGroup
    name="preference"
    tag="div"
    class="hidden w-full auto-rows-fr gap-2 lg:grid"
    style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr))"
  >
    <!-- Droppable Slot Container (Full cell area) -->
    <div
      v-for="(unit, index) in localPreferences"
      :key="unit.id"
      class="relative flex min-h-[160px] items-center justify-center p-2"
      @drop.prevent="handleDrop($event, index)"
      @dragover.prevent
      @dragenter.prevent
    >
      <!-- Fixed Numbered Slot (Background) -->
      <div
        class="pointer-events-none absolute inset-0 flex items-end justify-end rounded-lg border-2 border-dashed border-border-subtle bg-surface-sunken/30 pr-4 pb-4"
      >
        <span class="text-4xl font-bold text-text-subtle/30 select-none">{{ index + 1 }}</span>
      </div>

      <!-- Movable Unit Card (Foreground) - Inset from slot edges -->
      <div
        class="group relative z-10 h-[calc(100%-12px)] w-[calc(100%-12px)] rounded-lg border-2 border-border bg-surface/50 shadow-sm backdrop-blur-[2px] transition-all select-none"
        :class="[
          disabled ? 'cursor-wait' : 'cursor-move',
          draggedIndex === index
            ? 'scale-95 rotate-2 opacity-40'
            : 'hover:scale-[1.02] hover:shadow-md',
        ]"
        :style="{
          transform:
            draggedIndex === index
              ? 'scale(0.95) rotate(2deg)'
              : `rotate(${getUnitRotation(unit.id)}deg)`,
        }"
        :draggable="!disabled"
        @dragstart="handleDragStart($event, index)"
        @dragend.prevent="handleDragEnd"
      >
        <!-- Priority Badge Slot (for top 3) -->
        <slot name="priority-badge" :unit="unit" :index="index">
          <!-- Default priority badge not shown unless slot is provided -->
        </slot>

        <div class="flex h-full flex-col items-center justify-center gap-3 p-4">
          <!-- Unit Content Slot -->
          <div class="flex flex-1 items-center justify-center text-center">
            <slot name="unit-content" :unit="unit" :index="index">
              <!-- Default content if slot not provided -->
              <h3 class="font-mono text-xl leading-tight font-semibold text-text">
                {{ unit.identifier }}
              </h3>
            </slot>
          </div>

          <!-- Drag Indicator & Controls Container -->
          <PreferencesControls
            :index="index"
            :maxIndex="localPreferences.length - 1"
            :disabled="disabled"
            @up="moveUp(index)"
            @down="moveDown(index)"
          />
        </div>
      </div>
    </div>
  </TransitionGroup>
</template>

<style scoped>
/* Smooth transitions for preference reordering */
.preference-move,
.preference-enter-active,
.preference-leave-active {
  transition: all 400ms cubic-bezier(0.4, 0, 0.2, 1);
}

.preference-enter-from,
.preference-leave-to {
  opacity: 0;
  transform: scale(0.8) translateY(-20px);
}

.preference-leave-active {
  position: absolute;
}
</style>

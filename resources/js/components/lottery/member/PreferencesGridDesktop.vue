<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
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
watch(() => props.preferences, (newPreferences: Unit[]) => {
  localPreferences.splice(0, localPreferences.length, ...newPreferences);
}, { deep: true });

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
    class="hidden lg:grid gap-2 auto-rows-fr w-full"
    style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr))"
  >
    <!-- Droppable Slot Container (Full cell area) -->
    <div
      v-for="(unit, index) in localPreferences"
      :key="unit.id"
      class="relative flex items-center justify-center min-h-[160px] p-2"
      @drop.prevent="handleDrop($event, index)"
      @dragover.prevent
      @dragenter.prevent
    >
      <!-- Fixed Numbered Slot (Background) -->
      <div class="absolute inset-0 rounded-lg border-2 border-dashed border-border-subtle bg-surface-sunken/30 flex items-end justify-end pb-4 pr-4 pointer-events-none">
        <span class="text-4xl font-bold text-text-subtle/30 select-none">{{ index + 1 }}</span>
      </div>

      <!-- Movable Unit Card (Foreground) - Inset from slot edges -->
      <div
        class="relative z-10 group w-[calc(100%-12px)] h-[calc(100%-12px)] rounded-lg border-2 shadow-sm select-none transition-all bg-surface/50 border-border backdrop-blur-[2px]"
        :class="[
          disabled ? 'cursor-wait' : 'cursor-move',
          draggedIndex === index ? 'opacity-40 scale-95 rotate-2' : 'hover:shadow-md hover:scale-[1.02]',
        ]"
        :style="{ transform: draggedIndex === index ? 'scale(0.95) rotate(2deg)' : `rotate(${getUnitRotation(unit.id)}deg)` }"
        :draggable="!disabled"
        @dragstart="handleDragStart($event, index)"
        @dragend.prevent="handleDragEnd"
      >
        <!-- Priority Badge Slot (for top 3) -->
        <slot name="priority-badge" :unit="unit" :index="index">
          <!-- Default priority badge not shown unless slot is provided -->
        </slot>

        <div class="p-4 flex flex-col items-center justify-center gap-3 h-full">
          <!-- Unit Content Slot -->
          <div class="text-center flex-1 flex items-center justify-center">
            <slot name="unit-content" :unit="unit" :index="index">
              <!-- Default content if slot not provided -->
              <h3 class="font-semibold text-text text-xl leading-tight font-mono">{{ unit.identifier }}</h3>
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

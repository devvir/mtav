<script setup lang="ts">
import { useDragAndDrop } from '@/composables/useDragAndDrop';
import { GripVerticalIcon } from 'lucide-vue-next';
import PreferencesControls from './PreferencesControls.vue';

const props = defineProps<{
  preferences: Unit[];
  disabled: boolean;
}>();

const emit = defineEmits<{
  change: [updatedPreferences: Unit[]];
}>();

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
  <div class="w-full space-y-3 lg:hidden">
    <div
      v-for="(unit, index) in localPreferences"
      :key="unit.id"
      class="group relative w-full rounded-lg border border-accent px-4 py-3 select-none hover:shadow-sm"
      :class="[
        disabled ? 'cursor-wait' : 'cursor-move',
        draggedIndex !== null ? 'bg-accent/10' : 'bg-accent/5',
      ]"
      :draggable="!disabled"
      @dragstart="handleDragStart($event, index)"
      @drop.prevent="handleDrop($event, index)"
      @dragover.prevent
      @dragenter.prevent
      @dragend.prevent="handleDragEnd"
    >
      <div class="flex items-center gap-4">
        <!-- Drag Handle -->
        <div
          class="touch-none"
          :class="disabled ? 'cursor-wait opacity-50' : 'cursor-grab active:cursor-grabbing'"
        >
          <GripVerticalIcon class="h-5 w-5 text-text-subtle group-hover:text-text" />
        </div>

        <!-- Preference Rank -->
        <div class="flex-shrink-0">
          <div
            class="flex h-8 w-8 items-center justify-center rounded-full bg-accent text-sm font-medium text-accent-foreground"
          >
            {{ index + 1 }}
          </div>
        </div>

        <!-- Unit Details Slot -->
        <div class="min-w-0 flex-1">
          <slot name="unit-details" :unit="unit" :index="index">
            <!-- Default content if slot not provided -->
            <div class="flex items-center gap-2">
              <h3 class="truncate font-medium text-text">{{ unit.identifier }}</h3>
            </div>
          </slot>
        </div>

        <!-- Move Controls (Accessibility) -->
        <PreferencesControls
          layout="vertical"
          :index="index"
          :maxIndex="localPreferences.length - 1"
          :disabled="disabled"
          @up="moveUp(index)"
          @down="moveDown(index)"
        />
      </div>
    </div>
  </div>
</template>

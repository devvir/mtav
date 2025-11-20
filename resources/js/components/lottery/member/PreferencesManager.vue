<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardContent, CardHeader } from '@/components/card';
import { Button } from '@/components/ui/button';
import { GripVerticalIcon, ChevronUpIcon, ChevronDownIcon, HeartIcon, MapPinIcon } from 'lucide-vue-next';

const props = defineProps<{
  units: Unit[];
}>();

const preferences = reactive(props.units);
const draggedIndex = ref<number | null>(null);

const form = useForm({ preferences });

const move = (from: number, to: number) => {
  preferences.splice(to, 0, preferences.splice(from, 1)[0]);

  form.preferences = preferences;
  form.post(route('lottery.preferences'), { preserveScroll: true });
}

// Drag and drop
const onDrag = (_e: DragEvent, index: number) => {
  draggedIndex.value = index;
};

const onDrop = (e: DragEvent, targetIndex: number) => {
  (e.target as HTMLElement).classList.remove('opacity-99');
  if (draggedIndex.value !== targetIndex) {
    move(draggedIndex.value, targetIndex);
  }
  draggedIndex.value = null;
};

// const onDragOver = (e: DragEvent) => {
//   e.dataTransfer!.dropEffect = 'move';
// };

// Accessibility handlers for keyboard navigation
const moveUp = (index: number) => {
  if (index > 0) move(index, index - 1);
};

const moveDown = (index: number) => {
  if (index < preferences.length - 1) move(index, index + 1);
};

const getPreferenceLabel = (index: number) => {
  return _(`Choice #{numeral}`, { numeral: '' + (index + 1) });
};
</script>

<template>
  <Card class="h-full flex flex-col">
    <CardHeader :title="_('Unit Preferences')">
      {{ _('Drag and drop to rank your preferred units or use the arrow buttons') }}
    </CardHeader>

    <CardContent class="flex-1 overflow-y-auto">
      <!-- Preferences List -->
      <div class="space-y-3 w-full">
        <div
          v-for="(unit, index) in preferences"
          :key="unit.id"
          class="group relative rounded-lg border px-4 hover:shadow-sm border-accent select-none w-full"
        :class="[
          form.processing ? 'cursor-wait' : 'cursor-move',
          draggedIndex !== null ? 'bg-accent/10' : 'bg-accent/5'
        ]"
        :draggable="!form.processing"
        @dragstart="onDrag($event, index)"
        @drop.prevent="onDrop($event, index)"
        @dragover.prevent
        @dragenter.prevent
        @dragend.prevent="draggedIndex = null"
      >
        <div class="flex items-center gap-4">
          <!-- Drag Handle -->
          <div class=" touch-none"
            :class="form.processing ? 'cursor-wait opacity-50' : 'cursor-grab active:cursor-grabbing'">
            <GripVerticalIcon class="h-5 w-5 text-text-subtle group-hover:text-text" />
          </div>

          <!-- Preference Rank -->
          <div class="flex-shrink-0">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-accent text-sm font-medium text-accent-foreground">
              {{ index + 1 }}
            </div>
          </div>

          <!-- Unit Details -->
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <h3 class="font-medium text-text">{{ unit.identifier }}</h3>
              <span v-if="index < 3" class="ml-auto inline-flex items-center gap-1 rounded-full bg-primary px-2 py-1 text-xs font-medium text-primary-foreground">
                <HeartIcon class="size-3" /> {{ getPreferenceLabel(index) }}
              </span>
            </div>
          </div>

          <!-- Move Controls (Accessibility) -->
          <div class="flex flex-col gap-1 opacity-0 transition-opacity group-hover:opacity-100">
            <Button
              @click="moveUp(index)"
              :disabled="index === 0 || form.processing"
              variant="ghost"
              size="sm"
              class="h-6 w-6 p-0"
              :aria-label="_('Move up')"
            ><ChevronUpIcon class="h-3 w-3" /></Button>

            <Button
              @click="moveDown(index)"
              :disabled="index === preferences.length - 1 || form.processing"
              variant="ghost"
              size="sm"
              class="h-6 w-6 p-0"
              :aria-label="_('Move down')"
            ><ChevronDownIcon class="h-3 w-3" /></Button>
          </div>
        </div>
        </div>
      </div>

      <div v-if="preferences.length === 0" class="py-12 text-center">
        <MapPinIcon class="mx-auto h-12 w-12 text-text-subtle" />
        <p class="mt-2 text-sm text-text-muted">
          {{ _('There are no units in your assigned unit type to set preferences for.') }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>
<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { Unit } from '@/types/models';
import { ArrowDown, ArrowUp, GripVertical } from 'lucide-vue-next';

/**
 * A family's ranking: most wanted first. Reorder by dragging (pointer) or with the
 * arrow buttons (touch, keyboard, screen readers); every move is announced.
 */
const props = defineProps<{ disabled?: boolean }>();

const units = defineModel<Unit[]>({ required: true });
const emit = defineEmits<{ change: [units: Unit[]] }>();

const announcement = ref('');
const dragging = ref<number | null>(null);

function move(from: number, to: number) {
  if (props.disabled || to < 0 || to >= units.value.length || from === to) return;

  const next = [...units.value];
  const [unit] = next.splice(from, 1);
  next.splice(to, 0, unit);
  units.value = next;

  announcement.value = _('{unit} is now number {position}', {
    unit: unit.identifier,
    position: to + 1,
  });
  emit('change', next);
}

function drop(to: number) {
  if (dragging.value !== null) move(dragging.value, to);
  dragging.value = null;
}
</script>

<template>
  <ol class="space-y-2">
    <li
      v-for="(unit, index) in units"
      :key="unit.id"
      :draggable="!disabled"
      :class="
        cn(
          'flex min-h-14 items-center gap-3 rounded-xl border-2 border-border bg-surface px-3 transition',
          !disabled && 'cursor-grab active:cursor-grabbing',
          dragging === index && 'opacity-50',
          index === 0 && 'border-success/60',
        )
      "
      @dragstart="dragging = index"
      @dragend="dragging = null"
      @dragover.prevent
      @drop.prevent="drop(index)"
    >
      <span
        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-surface-sunken font-bold"
        >{{ index + 1 }}</span
      >
      <GripVertical
        v-if="!disabled"
        class="hidden size-5 shrink-0 text-text-subtle sm:block"
        aria-hidden="true"
      />
      <span class="min-w-0 flex-1 truncate font-semibold">{{ unit.identifier }}</span>
      <div v-if="!disabled" class="flex shrink-0 gap-1">
        <button
          type="button"
          class="flex size-11 items-center justify-center rounded-lg hover:bg-surface-interactive-hover disabled:opacity-30"
          :disabled="index === 0"
          :aria-label="_('Move {unit} up', { unit: unit.identifier })"
          @click="move(index, index - 1)"
        >
          <ArrowUp class="size-5" />
        </button>
        <button
          type="button"
          class="flex size-11 items-center justify-center rounded-lg hover:bg-surface-interactive-hover disabled:opacity-30"
          :disabled="index === units.length - 1"
          :aria-label="_('Move {unit} down', { unit: unit.identifier })"
          @click="move(index, index + 1)"
        >
          <ArrowDown class="size-5" />
        </button>
      </div>
    </li>
  </ol>
  <p class="sr-only" aria-live="polite">{{ announcement }}</p>
</template>

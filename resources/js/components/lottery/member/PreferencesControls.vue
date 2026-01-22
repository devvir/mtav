<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { ChevronDownIcon, ChevronUpIcon, GripVerticalIcon } from 'lucide-vue-next';
import MoveButton from './PreferencesControlsMoveButton.vue';

const emit = defineEmits<{
  up: [];
  down: [];
}>();

defineProps<{
  index: number;
  maxIndex: number;
  disabled?: boolean;
  layout?: 'horizontal' | 'vertical';
}>();
</script>

<template>
  <!-- Vertical layout (mobile) -->
  <div
    v-if="layout === 'vertical'"
    class="flex flex-col gap-1 opacity-0 transition-opacity group-hover:opacity-100"
  >
    <MoveButton @click="emit('up')" :disabled="index === 0 || disabled" :label="_('Move up')"
      ><ChevronUpIcon class="size-3"
    /></MoveButton>

    <MoveButton
      @click="emit('down')"
      :disabled="index === maxIndex || disabled"
      :label="_('Move down')"
      ><ChevronDownIcon class="size-3"
    /></MoveButton>
  </div>

  <!-- Horizontal layout (desktop) -->
  <div
    v-else
    class="mt-auto flex items-center gap-2 opacity-0 transition-opacity group-hover:opacity-100"
  >
    <MoveButton @click="emit('up')" :disabled="index === 0 || disabled" :label="_('Move up')"
      ><ChevronUpIcon class="size-4"
    /></MoveButton>

    <!-- Drag Handle -->
    <div
      class="touch-none"
      :class="disabled ? 'cursor-wait opacity-50' : 'cursor-grab active:cursor-grabbing'"
    >
      <GripVerticalIcon class="size-5 text-text-subtle" />
    </div>

    <MoveButton
      @click="emit('down')"
      :disabled="index === maxIndex || disabled"
      :label="_('Move down')"
      ><ChevronDownIcon class="size-4"
    /></MoveButton>
  </div>
</template>

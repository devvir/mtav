<script setup lang="ts">
import { Dropdown, DropdownContent, DropdownTrigger } from '@/components/dropdown';
import { _ } from '@/composables/useTranslations';
import { ChevronDown } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import type { OptionValue } from '.';

const selected = defineModel<OptionValue>();

const emit = defineEmits<{
  input: [value: OptionValue];
}>();

const props = defineProps<{
  options: Record<OptionValue, string>; // Keys can be string | number, values are display labels
  placeholder?: string;
  disabled?: boolean;
  class?: HTMLAttributes['class'];
}>();

const activeLabel = computed(() =>
  selected.value ? _(props.options[selected.value]) : (props.placeholder ?? _('Select option')),
);

const selectOption = (value: OptionValue, close: () => void) => {
  selected.value = value;
  emit('input', value);
  close();
};
</script>

<template>
  <Dropdown :disabled :class="$props.class" v-slot="{ isOpen, close }">
    <DropdownTrigger
      class="flex min-h-[48px] w-full min-w-48 items-center justify-between gap-2 rounded-xl border-2 border-border bg-surface/50 px-4 py-2 text-sm font-medium whitespace-nowrap backdrop-blur-sm transition-all duration-200 hover:bg-surface-elevated"
      :class="{
        'ring-2 ring-focus-ring': isOpen,
        'cursor-not-allowed opacity-50': disabled,
      }"
    >
      <span class="truncate text-text">{{ activeLabel }}</span>
      <ChevronDown
        class="size-4 text-text-muted transition-transform"
        :class="{ 'rotate-180': isOpen }"
      />
    </DropdownTrigger>

    <DropdownContent
      class="mt-2 max-h-60 w-full divide-y divide-border/30 overflow-auto rounded-xl border border-border bg-background/95 shadow-lg"
    >
      <template v-for="[value, label] in Object.entries(options)" :key="value">
        <button
          type="button"
          class="flex w-full items-center px-4 py-3 text-left text-sm transition-colors hover:bg-surface-elevated"
          :class="value === selected ? 'bg-primary/10 font-medium text-primary' : 'text-text'"
          @click="selectOption(value, close)"
        >
          {{ _(label) }}
        </button>
      </template>
    </DropdownContent>
  </Dropdown>
</template>

<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import Dropdown from '../dropdown/Dropdown.vue';
import DropdownContent from '../dropdown/DropdownContent.vue';
import DropdownTrigger from '../dropdown/DropdownTrigger.vue';
import FormSelectAddOption from './FormSelectAddOption.vue';
import * as keys from './keys';
import { SelectAddOption, ValueType } from '.';

const selected = defineModel<(string | number)[]>();

const props = defineProps<{
  multiple?: boolean;
  options: { [key: string | number]: string };
  placeholder?: string;
  disabled?: boolean;
  displayId?: boolean;
  create?: SelectAddOption;
  dropdownSlot: string;
}>();

const sortedOptions = computed(() => {
  const keys = Object.keys(props.options);

  // If options are only "0" and "1", sort as "1" then "0"
  if (keys.length === 2 && keys.includes('0') && keys.includes('1')) {
    return [['1', props.options['1']], ['0', props.options['0']]];
  }

  // If all keys are numeric, sort by label (value)
  if (keys.every(key => !isNaN(Number(key)))) {
    return Object.entries(props.options).sort(([, a], [, b]) => (a as string).localeCompare(b as string));
  }

  // Otherwise, return as is
  return Object.entries(props.options);
});

const modelLabel = computed(() => {
  if (props.multiple) {
    return selected.value?.map((value: ValueType) => props.options[value]).join(', ') || '';
  }

  return selected.value ? props.options[selected.value[0]] : '';
});

const toggleOption = (value: string | number, closeModal: () => void) => {
  if (!props.multiple) {
    selected.value = [value];
    closeModal();
  } else if (selected.value?.some((option: ValueType) => option == value)) {
    selected.value = selected.value.filter((v: ValueType) => v != value);
  } else {
    (selected.value as (string | number)[]).push(value);
  }
};

const isDisabled = computed(() => props.disabled || Object.values(props.options).length < 2);

const pauseModalClosing = inject(keys.pauseModalClosing) as (pause?: boolean) => void;
</script>

<template>
  <div class="relative size-full" v-bind="{ ...$props, ...$attrs }">
    <Dropdown
      v-slot="{ isOpen, close }"
      :disabled="isDisabled"
      class="size-full"
      @open="pauseModalClosing()"
      @close="pauseModalClosing(false)"
    >
      <!-- Visible input-->
      <input
        class="size-full pointer-events-none truncate px-3 pr-10 text-text outline-0"
        tabindex="-1"
        :class="{ 'placeholder-transparent': isOpen }"
        :value="modelLabel"
        :placeholder="disabled ? '' : (placeholder ?? _('Click to select an option'))"
      />

      <DropdownTrigger
        :title="modelLabel || (isDisabled ? '' : (placeholder ?? _('Click to select an option')))"
        class="group absolute inset-0"
      >
        <svg
          class="absolute top-1/2 right-3 size-6 -translate-y-1/2 stroke-text-muted transition-all peer-hocus:stroke-text"
          :class="{ 'rotate-180': isOpen, hidden: isDisabled }"
          viewBox="0 0 24 24"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            d="M19 9L12 16L5 9"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </DropdownTrigger>

      <Teleport :to="dropdownSlot" defer>
        <DropdownContent
          class="unique-class group top-1 z-2 col-span-2 grid w-full origin-top cursor-pointer grid-cols-[auto_1fr] overflow-hidden overflow-y-auto rounded-b-xl border-2 border-t-0 border-border bg-surface-elevated text-text shadow-xl backdrop-blur-sm transition-all duration-200 @md:col-start-2 @md:rounded-b-2xl"
          :class="{ 'invisible scale-y-0 opacity-0': !isOpen }"
        >
          <FormSelectAddOption
            v-if="create"
            v-bind="{ ...create, open: isOpen }"
            class="absolute inset-0 top-auto z-2 col-span-2 h-16 border-t-2 border-border"
          />

          <ul
            class="col-span-2 grid max-h-72 auto-rows-auto grid-cols-subgrid gap-0 divide-y divide-border-subtle overflow-auto overflow-y-auto"
            :class="{ 'mb-18': create }"
          >
            <li
              v-for="[value, label] in sortedOptions"
              :key="value"
              tabindex="0"
              class="col-span-2 grid size-full grid-cols-subgrid items-center gap-base px-4 py-3 text-base transition-all"
              :class="
                selected?.some((option: ValueType) => option == value)
                  ? 'bg-interactive font-semibold text-interactive-foreground'
                  : 'hover:bg-surface-interactive-hover hover:text-text focus:bg-surface-interactive-hover focus:text-text focus:outline-none'
              "
              @click.prevent.stop="toggleOption(value, close)"
              @keyup.enter="toggleOption(value, close)"
            >
              <div v-if="displayId" class="text-xs text-text-subtle">id: {{ value }}</div>
              <div :class="{ 'col-span-2': !displayId }">{{ label }}</div>
            </li>
          </ul>
        </DropdownContent>
      </Teleport>
    </Dropdown>
  </div>
</template>

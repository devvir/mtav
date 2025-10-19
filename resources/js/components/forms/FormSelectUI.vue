<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import Dropdown from '../dropdown/Dropdown.vue';
import DropdownContent from '../dropdown/DropdownContent.vue';
import DropdownTrigger from '../dropdown/DropdownTrigger.vue';
import FormSelectAddOption from './FormSelectAddOption.vue';
import * as keys from './keys';
import { SelectAddOption } from './types';

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

const modelLabel = computed(() => {
  if (props.multiple) {
    return selected.value?.map((value) => props.options[value]).join(', ') || '';
  }

  return selected.value ? props.options[selected.value[0]] : '';
});

const toggleOption = (value: string | number, closeModal: () => void) => {
  if (!props.multiple) {
    selected.value = [value];
    closeModal();
  } else if (selected.value?.includes(value)) {
    selected.value = selected.value.filter((v) => v !== value);
  } else {
    (selected.value as (string | number)[]).push(value);
  }
};

const pauseModalClosing = inject(keys.pauseModalClosing) as (pause?: boolean) => void;
</script>

<template>
  <div class="relative" v-bind="{ ...$props, ...$attrs }">
    <Dropdown
      v-slot="{ isOpen, close }"
      :disabled="disabled"
      @open="pauseModalClosing()"
      @close="pauseModalClosing(false)"
    >
      <!-- Visible input-->
      <input
        class="pointer-events-none w-full truncate p-3 pr-10 outline-0"
        tabindex="-1"
        :class="{ 'bg-muted-foreground': isOpen }"
        :value="modelLabel"
        :placeholder="placeholder ?? _('Click to select an option')"
      />

      <DropdownTrigger
        :title="modelLabel || (disabled ? '' : (placeholder ?? _('Click to select an option')))"
        class="group absolute inset-0"
      >
        <svg
          class="absolute top-1/2 right-3 size-6 -translate-y-1/2 stroke-background/50 transition-transform peer-hocus:stroke-background"
          :class="{ 'rotate-180': isOpen, hidden: disabled }"
          viewBox="0 0 24 24"
          fill="currentColor"
          aria-hidden="true"
        >
          <path d="M19 9L12 16L5 9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </DropdownTrigger>

      <Teleport :to="dropdownSlot" defer>
        <DropdownContent
          class="unique-class group top-1 z-2 col-span-2 grid w-full origin-top cursor-pointer grid-cols-[auto_1fr] overflow-hidden overflow-y-auto rounded-b-lg border border-foreground/5 bg-background/93 text-foreground/70 shadow shadow-muted-foreground/40 backdrop-blur-sm transition-all duration-300 @md:col-start-2"
          :class="{ 'invisible -rotate-x-90 opacity-0': !isOpen }"
        >
          <FormSelectAddOption
            v-if="create"
            v-bind="{ ...create, open: isOpen }"
            class="absolute inset-0 top-auto z-2 col-span-2 h-16 border-t-2 border-foreground"
          />

          <ul
            class="col-span-2 grid max-h-72 auto-rows-auto grid-cols-subgrid gap-0 overflow-auto overflow-y-auto"
            :class="{ 'mb-18': create }"
          >
            <li
              v-for="(label, value) in options"
              :key="value"
              tabindex="0"
              class="col-span-2 grid size-full grid-cols-subgrid items-center gap-base px-3 py-1 leading-wide"
              :class="
                selected?.includes(value)
                  ? 'bg-accent text-accent-foreground'
                  : 'hover:bg-accent/70 hover:text-accent-foreground not-group-hover:focus:bg-accent/70 not-group-hover:focus:text-accent-foreground'
              "
              @click.prevent.stop="toggleOption(value, close)"
              @keyup.enter="toggleOption(value, close)"
            >
              <div v-if="displayId" class="text-xs opacity-80">id: {{ value }}</div>
              <div :class="{ 'col-span-2': !displayId }">{{ label }}</div>
            </li>
          </ul>
        </DropdownContent>
      </Teleport>
    </Dropdown>
  </div>
</template>

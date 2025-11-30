<script setup lang="ts">
import FormElement from './FormElement.vue';
import { ValueType } from '.';

const model = defineModel<ValueType>();

defineProps<{
  name: string;
  label?: string;
  type?: string;
  min?: number;
  max?: number;
  minlength?: number;
  maxlength?: number;
  disabled?: boolean;
  required?: boolean;
  placeholder?: string;
  autocomplete?: boolean;
}>();
</script>

<template>
  <FormElement
    v-if="type !== 'hidden'"
    v-slot="{ id }"
    v-bind="{ model, type, ...$props, ...$attrs }"
  >
    <input
      class="size-full bg-transparent px-3 text-text caret-interactive outline-0 focus:outline-0 [&::-webkit-calendar-picker-indicator]:opacity-100 [&::-webkit-calendar-picker-indicator]:cursor-pointer dark:[&::-webkit-calendar-picker-indicator]:filter-[invert(1)]"
      v-model="model"
      :id="id"
      :name
      :type
      :min
      :max
      :minlength
      :maxlength
      :disabled
      :required
      :placeholder
      :autocomplete="autocomplete ? 'on' : 'off'"
      :title="JSON.stringify({ ...$attrs })"
      :aria-label="label"
    />
  </FormElement>
</template>

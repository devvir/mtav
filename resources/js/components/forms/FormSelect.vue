<script setup lang="ts">
import FormElement from './FormElement.vue';
import FormSelectUI from './FormSelectUI.vue';
import { ValueType } from '.';

const originalModel = defineModel<ValueType | ValueType[]>();

const props = defineProps<{
  multiple?: boolean;
  name: string;
  label: string;
  options: { [key: string | number]: string };
  error?: string;
  hidden?: boolean;
}>();

const model = ref<ValueType[]>(
  Array.isArray(originalModel.value)
    ? originalModel.value
    : originalModel.value != null
      ? [originalModel.value]
      : [],
);

watch(
  model,
  (newValue: ValueType[]) => {
    originalModel.value = props.multiple ? newValue : newValue.at(0);
  },
  { deep: true },
);
</script>

<template>
  <FormElement v-bind="{ model, ...$props, ...$attrs }" :class="{ hidden }">
    <template v-slot:default="{ id, slotAfter }">
      <div class="relative">
        <select
          multiple
          v-model="model"
          :id
          :name="`${name}[]`"
          v-bind="$attrs"
          class="invisible absolute size-full"
          tabindex="-1"
        >
          <option
            v-for="(label, value) in options"
            :key="value"
            :value
            :selected="typeof model[value] !== 'undefined'"
          >
            {{ label }}
          </option>
        </select>

        <FormSelectUI
          v-model="model"
          v-bind="{ options, multiple, ...$attrs }"
          :class="error ? 'border-red-600' : ''"
          :dropdownSlot="slotAfter"
        />
      </div>
    </template>
  </FormElement>
</template>

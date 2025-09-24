<script setup lang="ts">
import FormElement from './FormElement.vue';
import FormSelectUI from './FormSelectUI.vue';
import { ValueType } from './types';

const model = defineModel<ValueType>();

defineProps<{
  name: string;
  label: string;
  options: { [key: string | number]: string };
  error?: string;
}>();
</script>

<template>
  <FormElement v-bind="{ model, ...$props, ...$attrs }">
    <template v-slot:default="{ id, slotAfter }">
      <div class="relative">
        <input
          v-model="model"
          v-bind="{ id, name, ...$attrs }"
          class="absolute size-full text-transparent"
          tabindex="-1"
        />

        <FormSelectUI
          v-model="model"
          v-bind="{ options, ...$attrs }"
          :class="error ? 'border-red-600' : ''"
          :dropdownSlot="slotAfter"
          @update:selected="model = $event"
        />
      </div>
    </template>
  </FormElement>
</template>

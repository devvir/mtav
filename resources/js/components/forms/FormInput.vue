<script setup lang="ts">
import FormElement from './FormElement.vue';
import { ValueType } from './types';

const model = defineModel<ValueType>();

defineProps<{
  name: string;
  label?: string;
  type?: string;
}>();
</script>

<template>
  <FormElement v-if="type !== 'hidden'" v-slot="{ id }" v-bind="{ model, type, ...$props, ...$attrs }">
    <input
      class="w-full p-3 caret-accent-foreground outline-0"
      v-model="model"
      v-bind="{ id, name, ...$attrs }"
      :aria-label="label"
    />
  </FormElement>
</template>

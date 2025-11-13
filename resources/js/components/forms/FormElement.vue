<script setup lang="ts">
import FormError from './FormError.vue';
import FormLabel from './FormLabel.vue';
import { ValueType } from './types';

const props = defineProps<{
  model: ValueType | ValueType[];
  name: string;
  label?: string;
  disabled?: boolean;
  error?: string;
}>();

const id = `${useId()}-${props.name}`;
const slotAfterId = `after-slot-${id}`;

const valueProvided = computed<boolean>(() =>
  Array.isArray(props.model) ? !!props.model.length : !!props.model,
);
</script>

<template>
  <div class="col-span-2 grid-cols-subgrid grid-rows-[1fr_1rem] @md:grid">
    <div
      class="group relative z-1 col-span-2 grid-cols-subgrid items-center overflow-hidden rounded-xl border transition-all @md:grid @md:rounded-2xl"
      :class="{
        'border-border': !disabled,
        'focus-within:border-interactive focus-within:ring-2 focus-within:ring-interactive/20':
          !disabled,
        'not-focus-within:has-[:valid]:border-success/20': valueProvided && !disabled,
        'not-focus-within:has-[:invalid]:border-error/60': valueProvided && !disabled,
        'border-border-subtle': disabled,
      }"
    >
      <FormLabel
        v-if="label"
        :forId="id"
        v-bind="{ label, ...$props, ...$attrs }"
        class="border-r-2 border-border bg-surface-sunken px-3 py-3 font-semibold text-surface-sunken-foreground @max-md:border-r-0 @max-md:border-b-2"
        :class="{
          'group-focus-within:bg-surface-elevated group-focus-within:text-text': !disabled,
          'opacity-50': disabled,
        }"
      />

      <div
        class="h-full min-h-12 bg-surface text-lg text-text transition-colors"
        :class="{
          'group-focus-within:bg-surface': !disabled,
          'bg-surface-sunken opacity-50': disabled,
        }"
      >
        <slot :id="id" :slotAfter="`#${slotAfterId}`" class="relative" />
      </div>
    </div>

    <div class="col-span-2 grid h-0 grid-cols-subgrid">
      <FormError :error="error" class="mr-1 @md:col-start-2" />

      <div :id="slotAfterId" class="relative col-span-2 grid w-full grid-cols-subgrid" />
    </div>
  </div>
</template>

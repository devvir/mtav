<script setup lang="ts">
import FormError from './FormError.vue';
import FormLabel from './FormLabel.vue';
import { ValueType } from './types';

const props = defineProps<{
  model: ValueType;
  name: string;
  label?: string;
  disabled?: boolean;
  error?: string;
}>();

const id = `${useId()}-${props.name}`;
const slotAfterId = `after-slot-${id}`;
</script>

<template>
  <div class="col-span-2 grid-cols-subgrid grid-rows-[1fr_1rem] @md:grid">
    <div
      class="group relative z-1 col-span-2 grid-cols-subgrid items-center overflow-hidden rounded-xl border-1 border-background outline-muted-foreground/10 focus-within:outline-blue-400 @md:grid @md:rounded-2xl @md:border-6"
      :class="{
        'has-valid:outline-green-700/30 has-invalid:not-focus-within:outline-red-600/30': model,
        'outline-2': !disabled,
      }"
    >
      <FormLabel
        v-if="label"
        :forId="id"
        v-bind="{ label, ...$props, ...$attrs }"
        class="border-background bg-accent-foreground/15 px-1 text-foreground @max-md:px-3 @max-md:py-2 @max-md:pb-1 @md:border-r-8 @md:px-3"
        :class="{
          'group-focus-within:bg-accent-foreground/50 group-hover:not-group-focus-within:bg-accent-foreground/25':
            !disabled,
        }"
      />

      <div
        class="h-full min-h-12 bg-foreground/80 text-lg font-light text-background outline-0 transition-colors"
        :class="{
          'has-invalid:text-red-950 has-invalid:not-focus-within:text-red-800': model,
          'ring-blue-400 group-hover:bg-muted-foreground/90 focus-within:bg-muted-foreground focus-within:text-muted':
            !disabled,
          'bg-muted-foreground/45 font-extralight': disabled,
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

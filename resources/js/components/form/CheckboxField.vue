<script setup lang="ts">
import { useId } from 'vue';

/**
 * A checkbox with its label (and an optional explanation) beside it.
 */
defineProps<{ label: string; hint?: string; error?: string; name?: string }>();

const model = defineModel<boolean>({ default: false });
const id = useId();
</script>

<template>
  <div>
    <label :for="id" class="flex cursor-pointer items-start gap-3 rounded-lg py-1">
      <input
        :id
        v-model="model"
        :name
        type="checkbox"
        class="mt-0.5 size-5 shrink-0 cursor-pointer rounded border-2 border-border-strong accent-interactive focus-visible:ring-2 focus-visible:ring-focus-ring"
      />
      <span>
        <span class="block font-medium text-text">{{ label }}</span>
        <span v-if="hint" class="block text-sm text-text-muted">{{ hint }}</span>
      </span>
    </label>
    <p v-if="error" role="alert" class="mt-1 text-sm font-medium text-error">{{ error }}</p>
  </div>
</template>

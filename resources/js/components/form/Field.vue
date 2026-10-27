<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { useId } from 'vue';

/**
 * A labelled form field: label, optional hint, the input (slot), and its error.
 * The slot receives the id and aria attributes to put on the input.
 */
const props = defineProps<{
  label: string;
  hint?: string;
  error?: string;
  required?: boolean;
  optional?: boolean;
}>();

const id = useId();
const hintId = `${id}-hint`;
const errorId = `${id}-error`;

const describedBy = computed(
  () => [props.hint && hintId, props.error && errorId].filter(Boolean).join(' ') || undefined,
);
</script>

<template>
  <div class="space-y-1.5">
    <label :for="id" class="flex items-baseline gap-2 text-sm font-medium text-text">
      {{ label }}
      <span v-if="required" class="text-error" aria-hidden="true">*</span>
      <span v-else-if="optional" class="text-xs font-normal text-text-subtle">{{
        _('optional')
      }}</span>
    </label>

    <slot
      :id="id"
      :aria-describedby="describedBy"
      :aria-invalid="error ? true : undefined"
      :required="required"
    />

    <p v-if="hint" :id="hintId" class="text-sm text-text-muted">{{ hint }}</p>

    <Transition
      enter-active-class="transition duration-200"
      enter-from-class="-translate-y-1 opacity-0"
      leave-active-class="transition duration-150"
      leave-to-class="opacity-0"
    >
      <p v-if="error" :id="errorId" role="alert" class="text-sm font-medium text-error">
        {{ error }}
      </p>
    </Transition>
  </div>
</template>

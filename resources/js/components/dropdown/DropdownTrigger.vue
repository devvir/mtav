<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { CloseAction, OpenAction } from '.';
import * as keys from './keys';

defineProps<{
  class?: HTMLAttributes['class'];
}>();

const isOpen = inject(keys.isOpen) as Ref<boolean>;
const disabled = inject(keys.disabled) as Ref<boolean>;
const close = inject(keys.close) as (action: CloseAction) => void;
const toggle = inject(keys.toggle) as (action: OpenAction | CloseAction) => void;

/**
 * Differentiate between actual mouse clicks and keyboard-triggered clicks (both
 * trigger the @click handler). For broader compatibility and accurate reporting
 * to this component's listeners, we want to handle both scenarios differently.
 */
const isMouseEvent = (e: MouseEvent | KeyboardEvent) => e.detail > 0;
</script>

<template>
  <button
    :class="cn(['w-full', { 'cursor-pointer': !disabled }], $props.class)"
    aria-haspopup="true"
    :aria-expanded="isOpen"
    @click.prevent.stop="isMouseEvent($event) ? toggle('click') : null"
    @keyup.enter.stop.prevent="toggle('enter')"
    @keyup.space.stop.prevent="toggle('space')"
    @keyup.esc.stop.prevent="close('escape')"
    :tabindex="disabled ? -1 : 0"
  >
    <slot />
  </button>
</template>

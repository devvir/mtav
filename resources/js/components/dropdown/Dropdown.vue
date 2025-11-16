<script setup lang="ts">
import { DropdownUsage } from '.';
import * as exposed from './exposed';
import type { CloseAction, OpenAction } from './types.d';

const emits = defineEmits<{ open: [OpenAction]; close: [CloseAction] }>();
const props = defineProps<{ disabled?: boolean }>();

const isOpen = ref(false);

const open = (action?: OpenAction) => {
  if (isOpen.value || props.disabled) return;

  isOpen.value = true;
  emits('open', action ?? 'programmatic');
};

const close = (action?: CloseAction) => {
  if (!isOpen.value || (props.disabled && action !== 'disabled')) return;

  isOpen.value = false;
  emits('close', action ?? 'programmatic');
};

const toggle = (action?: OpenAction | CloseAction) => {
  if (isOpen.value) {
    close(action as CloseAction);
  } else {
    open(action as OpenAction);
  }
};

const dropdown = useTemplateRef<HTMLElement>('dropdown');
onClickOutside(dropdown, () => close('click-outside'), { ignore: ['.dropdown-content'] });

watchEffect(() => props.disabled && close('disabled'));

provide(exposed.isOpen, isOpen);
provide(exposed.disabled, props.disabled);
provide(exposed.open, open);
provide(exposed.close, close);
provide(exposed.toggle, toggle);
</script>

<template>
  <div ref="dropdown" class="relative">
    <slot :isOpen :open :close :toggle>
      <DropdownUsage />
    </slot>
  </div>
</template>

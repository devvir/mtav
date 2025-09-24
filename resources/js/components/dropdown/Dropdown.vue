<script setup lang="ts">
import { CloseAction, OpenAction } from '.';
import DropdownUsage from './DropdownUsage.vue';

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
onClickOutside(dropdown, () => close('click-outside'));

watchEffect(() => props.disabled && close('disabled'));

provide('isOpen', isOpen);
provide('disabled', props.disabled);
provide('open', open);
provide('close', close);
provide('toggle', toggle);
</script>

<template>
  <div ref="dropdown" class="relative">
    <slot :isOpen :open :close :toggle>
      <DropdownUsage />
    </slot>
  </div>
</template>

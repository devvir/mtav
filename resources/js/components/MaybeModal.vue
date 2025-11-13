<script setup lang="ts">
import { Modal, useModal } from '@inertiaui/modal-vue';
import type { HTMLAttributes } from 'vue';

defineProps<{
  panelClasses?: HTMLAttributes['class'];
  paddingClasses?: HTMLAttributes['class'];
}>();

const modal = useTemplateRef<any>('modal');

const onNavigateDetacher = router.on('navigate', () => modal.value?.close());
onUnmounted(onNavigateDetacher);

// TODO : change close-explicitly to custom prop confirmClosing
// When confirmClosing is true:
// - disable esc and click-outside (i.e. set closeExplicitly = true)
// - intercept clicks on close button to prompt the user for confirmation first
</script>

<template>
  <Modal
    ref="modal"
    v-if="useModal()"
    v-slot="modal"
    :panel-classes="panelClasses"
    :padding-classes="paddingClasses"
  >
    <slot v-bind="modal" />
  </Modal>

  <div v-else class="size-full">
    <slot />
  </div>
</template>

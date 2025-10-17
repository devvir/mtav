<script setup lang="ts">
import { Modal, useModal } from '@inertiaui/modal-vue';

const modal = useTemplateRef<any>('modal');

router.on('navigate', () => modal.value?.close());

// TODO : change close-explicitly to custom prop confirmClosing
// When confirmClosing is true:
// - disable esc and click-outside (i.e. set closeExplicitly = true)
// - intercept clicks on close button to prompt the user for confirmation first
</script>

<template>
  <Modal ref="modal" v-if="useModal()" v-slot="modal">
    <slot v-bind="modal" />
  </Modal>

  <div v-else class="size-full">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { MAYBEMODAL } from '@/composables/useInertiaUIModal';
import { cn } from '@/lib/utils';
import { Modal, useModal } from '@inertiaui/modal-vue';
import type { HTMLAttributes } from 'vue';

defineProps<{
  panelClasses?: HTMLAttributes['class'];
  paddingClasses?: HTMLAttributes['class'];
  class?: HTMLAttributes['class'];
}>();

const modal = useTemplateRef<any>('modal');

provide(MAYBEMODAL, modal || { close: () => null });

const onNavigateDetacher = router.on('navigate', () => modal.value?.close());
onUnmounted(onNavigateDetacher);

// TODO : change close-explicitly to custom prop confirmClosing
// When confirmClosing is true:
// - disable esc and click-outside (i.e. set closeExplicitly = true)
// - intercept clicks on close button to prompt the user for confirmation first
</script>

<template>
  <Modal
    v-if="useModal()"
    v-slot="modal"
    ref="modal"
    :panel-classes="cn('modalPanel', panelClasses, $props.class)"
    :padding-classes="cn('modalPadding', paddingClasses)"
  >
    <slot v-bind="modal" />
  </Modal>

  <div v-else :class="cn('size-full', $props.class)">
    <slot :close="() => null" />
  </div>
</template>

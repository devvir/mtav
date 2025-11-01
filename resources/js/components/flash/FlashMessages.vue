<script setup lang="ts">
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FlashMessage from './FlashMessage.vue';
import { useFlashMessages } from './useFlashMessages';

const props = defineProps<{
  noDismiss?: boolean;
}>();

const page = usePage();
const { messageStack, hasVisibleMessages, flash, removeMessage } = useFlashMessages({
  skipInertiaWatcher: true, // We handle it manually to respect noDismiss prop
});

// Watch for flash messages from Inertia page props
// Override the default watcher to respect noDismiss prop
watch(
  () => page.props.flash,
  (flashProps) => {
    if (!flashProps) return;

    const timeout = props.noDismiss ? 0 : undefined; // 0 = no auto-dismiss, undefined = default

    if (flashProps.success) flash(flashProps.success, 'success', timeout);
    if (flashProps.info) flash(flashProps.info, 'info', timeout);
    if (flashProps.warning) flash(flashProps.warning, 'warning', timeout);
    if (flashProps.error) flash(flashProps.error, 'error', timeout);
  },
  { immediate: true }
);
</script>

<template>
  <div v-if="hasVisibleMessages" class="w-full max-w-4xl flex flex-col gap-2">
    <FlashMessage
      v-for="message in messageStack"
      :key="message.id"
      :type="message.type"
      :message="message.message"
      @dismiss="removeMessage(message.id)"
    />
  </div>
</template>

import { FlashProps } from '@/types/inertia';
import { usePage } from '@inertiajs/vue3';

export type MessageType = 'success' | 'info' | 'warning' | 'error';

export interface FlashMessage {
  id: string;
  type: MessageType;
  message: string;
  multiline?: boolean;
  timeoutId?: number;
}

const AUTO_DISMISS_MS = 10000;

let messageIdCounter = 0;

export function useFlashMessages(options: { skipInertiaWatcher?: boolean } = {}) {
  const page = usePage();
  const messageStack = ref<FlashMessage[]>([]);

  const hasVisibleMessages = computed(() => messageStack.value.length > 0);

  /**
   * Display a flash message
   * @param message - The message to display
   * @param type - The message type (success, info, warning, error)
   * @param timeout - Auto-dismiss timeout in ms. Use 0 for no auto-dismiss (manual close only)
   * @param multiline - Allow message to wrap to multiple lines (default: false, truncates)
   */
  const flash = (
    message: string,
    type: MessageType = 'success',
    timeout: number = AUTO_DISMISS_MS,
    multiline: boolean = false,
  ) => {
    const id = `flash-${++messageIdCounter}`;
    const flashMessage: FlashMessage = { id, type, message, multiline };

    // Add to stack
    messageStack.value.push(flashMessage);

    // Only set auto-dismiss if timeout > 0
    if (timeout > 0) {
      const timeoutId = window.setTimeout(() => {
        removeMessage(id);
      }, timeout);

      // Store timeout ID so we can clear it if manually dismissed
      flashMessage.timeoutId = timeoutId;
    }
  };

  /**
   * Remove a message from the stack and clear its timeout
   */
  const removeMessage = (id: string) => {
    const index = messageStack.value.findIndex((msg: FlashMessage) => msg.id === id);
    if (index === -1) return;

    const message = messageStack.value[index];

    // Clear timeout if it exists
    if (message.timeoutId) {
      window.clearTimeout(message.timeoutId);
    }

    // Remove from stack
    messageStack.value.splice(index, 1);
  };

  /**
   * Clear all messages and their timeouts
   */
  const clearAll = () => {
    messageStack.value.forEach((msg: FlashMessage) => {
      if (msg.timeoutId) {
        window.clearTimeout(msg.timeoutId);
      }
    });
    messageStack.value = [];
  };

  // Watch for flash messages from Inertia page props
  // Can be disabled if the component handles it manually
  if (!options.skipInertiaWatcher) {
    watch(
      () => page.props.flash,
      (flashProps: FlashProps) => {
        if (!flashProps) return;

        // Add any flash messages from Inertia to the stack
        if (flashProps.success) flash(flashProps.success, 'success');
        if (flashProps.info) flash(flashProps.info, 'info');
        if (flashProps.warning) flash(flashProps.warning, 'warning');
        if (flashProps.error) flash(flashProps.error, 'error');
      },
      { immediate: true },
    );
  }

  // Cleanup on unmount
  onUnmounted(() => {
    clearAll();
  });

  return {
    messageStack,
    hasVisibleMessages,
    flash,
    removeMessage,
    clearAll,
  };
}

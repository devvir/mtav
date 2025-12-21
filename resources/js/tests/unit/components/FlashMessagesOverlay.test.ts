// Copilot - Pending review
import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import FlashMessagesOverlay from '@/components/flash/FlashMessagesOverlay.vue';
import { useFlashMessages } from '@/components/flash/useFlashMessages';

describe('FlashMessagesOverlay - Feature Tests', () => {
  describe('overlay visibility with flash messages', () => {
    it('overlay is hidden initially when no messages', () => {
      const wrapper = mount(FlashMessagesOverlay, {
        global: {
          stubs: {
            FlashMessages: {
              template: '<div class="flash-messages-container">Messages go here</div>',
            },
          },
        },
      });

      // The overlay div (with class "absolute") should not exist when hasVisibleMessages is false
      const overlayDiv = wrapper.find('.absolute');
      expect(overlayDiv.exists()).toBe(false);
    });

    it('overlay should show when a message is added to its instance', async () => {
      // Feature test: Can we add a message and see it appear in the overlay?
      const { flash, messageStack, hasVisibleMessages } = useFlashMessages();

      // Initially no messages
      expect(messageStack.value).toHaveLength(0);
      expect(hasVisibleMessages.value).toBe(false);

      // Add a message
      flash('Test message', 'success');

      // Now there should be a message
      expect(messageStack.value).toHaveLength(1);
      expect(hasVisibleMessages.value).toBe(true);
    });

    it('overlay should hide when last message is removed', async () => {
      // Feature test: Can we remove a message and see overlay hide?
      const { flash, removeMessage, messageStack, hasVisibleMessages } = useFlashMessages();

      // Add a message
      flash('Test message', 'success');
      expect(hasVisibleMessages.value).toBe(true);

      // Remove it by ID
      const messageId = messageStack.value[0].id;
      removeMessage(messageId);

      // Now it should be gone
      expect(messageStack.value).toHaveLength(0);
      expect(hasVisibleMessages.value).toBe(false);
    });

    it('overlay should hide when message auto-dismisses after timeout', async () => {
      // Feature test: Does auto-dismiss work?
      vi.useFakeTimers();

      const { flash, messageStack, hasVisibleMessages } = useFlashMessages();

      // Add a message with 1000ms timeout
      flash('Auto-dismiss message', 'success', 1000);
      expect(messageStack.value).toHaveLength(1);
      expect(hasVisibleMessages.value).toBe(true);

      // Fast forward time
      vi.runAllTimers();

      // Message should be gone
      expect(messageStack.value).toHaveLength(0);
      expect(hasVisibleMessages.value).toBe(false);

      vi.useRealTimers();
    });

    it('overlay with multiple messages shows until all are removed', async () => {
      const { flash, removeMessage, messageStack, hasVisibleMessages } = useFlashMessages();

      // Add multiple messages
      flash('Message 1', 'success');
      flash('Message 2', 'error');
      expect(hasVisibleMessages.value).toBe(true);
      expect(messageStack.value).toHaveLength(2);

      // Remove first message
      removeMessage(messageStack.value[0].id);
      expect(hasVisibleMessages.value).toBe(true); // Still has one message
      expect(messageStack.value).toHaveLength(1);

      // Remove last message
      removeMessage(messageStack.value[0].id);
      expect(hasVisibleMessages.value).toBe(false); // Now should hide
      expect(messageStack.value).toHaveLength(0);
    });
  });
});


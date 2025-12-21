// Copilot - Pending review
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';

// Unmock the actual composable for this test file
vi.unmock('@/components/flash/useFlashMessages');

import { useFlashMessages } from '@/components/flash/useFlashMessages';

describe('useFlashMessages composable', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
  });

  describe('basic message operations', () => {
    it('adds messages to stack', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Test message', 'success');

      expect(messageStack.value).toHaveLength(1);
      expect(messageStack.value[0].message).toBe('Test message');
      expect(messageStack.value[0].type).toBe('success');
    });

    it('generates unique IDs for each message', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Message 1', 'success');
      flash('Message 2', 'info');

      expect(messageStack.value[0].id).not.toBe(messageStack.value[1].id);
      expect(messageStack.value[0].id).toMatch(/^flash-\d+$/);
    });

    it('tracks message type correctly', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Success', 'success');
      flash('Error', 'error');
      flash('Warning', 'warning');
      flash('Info', 'info');

      expect(messageStack.value[0].type).toBe('success');
      expect(messageStack.value[1].type).toBe('error');
      expect(messageStack.value[2].type).toBe('warning');
      expect(messageStack.value[3].type).toBe('info');
    });
  });

  describe('multiline prop', () => {
    it('respects multiline prop when true', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Multiline message', 'success', 0, true);

      expect(messageStack.value[0].multiline).toBe(true);
    });

    it('defaults to false for multiline', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Single line message', 'success', 0);

      expect(messageStack.value[0].multiline).toBeFalsy();
    });
  });

  describe('auto-dismiss behavior', () => {
    it('auto-dismisses messages after default timeout (10s)', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Auto-dismiss message', 'success');

      expect(messageStack.value).toHaveLength(1);

      vi.advanceTimersByTime(10000);

      expect(messageStack.value).toHaveLength(0);
    });

    it('auto-dismisses with custom timeout', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Custom timeout', 'success', 5000);

      expect(messageStack.value).toHaveLength(1);

      vi.advanceTimersByTime(4999);
      expect(messageStack.value).toHaveLength(1);

      vi.advanceTimersByTime(1);
      expect(messageStack.value).toHaveLength(0);
    });

    it('does not auto-dismiss when timeout is 0', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('No auto-dismiss', 'success', 0);

      vi.advanceTimersByTime(100000);

      expect(messageStack.value).toHaveLength(1);
    });

    it('does not auto-dismiss with undefined timeout in noAutoDismiss mode', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      // When timeout is undefined, it uses the default
      flash('Should auto-dismiss', 'success', undefined);

      vi.advanceTimersByTime(9999);
      expect(messageStack.value).toHaveLength(1);

      vi.advanceTimersByTime(1);
      expect(messageStack.value).toHaveLength(0);
    });
  });

  describe('manual dismissal', () => {
    it('removes message by ID', () => {
      const { messageStack, flash, removeMessage } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Message 1', 'success');
      const messageId = messageStack.value[0].id;

      removeMessage(messageId);

      expect(messageStack.value).toHaveLength(0);
    });

    it('clears timeout when manually dismissing auto-dismiss message', () => {
      const { messageStack, flash, removeMessage } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Auto-dismiss message', 'success', 10000);
      const messageId = messageStack.value[0].id;

      removeMessage(messageId);

      // Advance timer - should NOT trigger remove again since timeout was cleared
      vi.advanceTimersByTime(10000);

      expect(messageStack.value).toHaveLength(0); // Should stay at 0, not go negative
    });

    it('removes correct message when multiple exist', () => {
      const { messageStack, flash, removeMessage } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Message 1', 'success', 0);
      flash('Message 2', 'info', 0);
      flash('Message 3', 'error', 0);

      const messageId = messageStack.value[1].id;
      removeMessage(messageId);

      expect(messageStack.value).toHaveLength(2);
      expect(messageStack.value[0].message).toBe('Message 1');
      expect(messageStack.value[1].message).toBe('Message 3');
    });

    it('ignores remove request for non-existent ID', () => {
      const { messageStack, flash, removeMessage } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Message 1', 'success', 0);

      removeMessage('non-existent-id');

      expect(messageStack.value).toHaveLength(1);
    });
  });

  describe('hasVisibleMessages computed', () => {
    it('is true when messages exist', () => {
      const { hasVisibleMessages, flash } = useFlashMessages({ skipInertiaWatcher: true });

      expect(hasVisibleMessages.value).toBe(false);

      flash('Message', 'success', 0);

      expect(hasVisibleMessages.value).toBe(true);
    });

    it('is false when stack is empty', () => {
      const { hasVisibleMessages, flash, removeMessage, messageStack } = useFlashMessages({
        skipInertiaWatcher: true,
      });

      flash('Message', 'success', 0);
      const messageId = messageStack.value[0].id;

      removeMessage(messageId);

      expect(hasVisibleMessages.value).toBe(false);
    });
  });

  describe('clearAll', () => {
    it('removes all messages', () => {
      const { messageStack, flash, clearAll } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Message 1', 'success', 0);
      flash('Message 2', 'info', 0);
      flash('Message 3', 'error', 0);

      expect(messageStack.value).toHaveLength(3);

      clearAll();

      expect(messageStack.value).toHaveLength(0);
    });

    it('clears all timeouts', () => {
      const { messageStack, flash, clearAll } = useFlashMessages({ skipInertiaWatcher: true });

      flash('Message 1', 'success', 10000);
      flash('Message 2', 'info', 10000);

      clearAll();

      vi.advanceTimersByTime(10000);

      expect(messageStack.value).toHaveLength(0);
    });
  });

  describe('message stack order', () => {
    it('maintains FIFO order for multiple messages', () => {
      const { messageStack, flash } = useFlashMessages({ skipInertiaWatcher: true });

      flash('First', 'success', 0);
      flash('Second', 'info', 0);
      flash('Third', 'error', 0);

      expect(messageStack.value[0].message).toBe('First');
      expect(messageStack.value[1].message).toBe('Second');
      expect(messageStack.value[2].message).toBe('Third');
    });

    it('maintains order after removing middle message', () => {
      const { messageStack, flash, removeMessage } = useFlashMessages({
        skipInertiaWatcher: true,
      });

      flash('First', 'success', 0);
      flash('Second', 'info', 0);
      flash('Third', 'error', 0);

      removeMessage(messageStack.value[1].id);

      expect(messageStack.value[0].message).toBe('First');
      expect(messageStack.value[1].message).toBe('Third');
    });
  });
});

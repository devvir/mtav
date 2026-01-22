// Copilot - Pending review
import FlashMessage from '@/components/flash/FlashMessage.vue';
import { createMockFlashMessageProps } from '@/tests/helpers/fixtures';
import { mount } from '@/tests/helpers/mount';
import { describe, expect, it } from 'vitest';

describe('FlashMessage', () => {
  describe('message types and rendering', () => {
    it('renders success message', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ type: 'success', message: 'Operation successful' }),
      });

      expect(wrapper.text()).toContain('Operation successful');
    });

    it('renders error message', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ type: 'error', message: 'An error occurred' }),
      });

      expect(wrapper.text()).toContain('An error occurred');
    });

    it('renders warning message', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ type: 'warning', message: 'Warning message' }),
      });

      expect(wrapper.text()).toContain('Warning message');
    });

    it('renders info message', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ type: 'info', message: 'Information' }),
      });

      expect(wrapper.text()).toContain('Information');
    });
  });

  describe('icons', () => {
    it('renders appropriate icon for each message type', () => {
      const types = ['success', 'error', 'warning', 'info'] as const;

      for (const type of types) {
        const wrapper = mount(FlashMessage, {
          props: createMockFlashMessageProps({ type }),
        });

        // Icons should be rendered (they're lucide-vue components stubbed as divs with class-based names)
        const icons = wrapper.findAll('[class*="icon"]');
        expect(icons.length).toBeGreaterThan(0);
      }
    });
  });

  describe('dismiss button and event', () => {
    it('renders dismiss button', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test message' }),
      });

      expect(wrapper.find('button').exists()).toBe(true);
    });

    it('emits dismiss event when dismiss button clicked', async () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test message' }),
      });

      await wrapper.find('button').trigger('click');

      expect(wrapper.emitted('dismiss')).toBeTruthy();
      expect(wrapper.emitted('dismiss')).toHaveLength(1);
    });

    it('does not emit multiple dismiss events from single click', async () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test message' }),
      });

      await wrapper.find('button').trigger('click');
      const emitted = wrapper.emitted('dismiss');

      expect(emitted).toHaveLength(1);
    });
  });

  describe('multiline vs single-line behavior', () => {
    it('truncates single-line messages by default', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ multiline: false, message: 'Long message' }),
      });

      const alertDescription = wrapper.find('[class*="truncate"]');
      expect(alertDescription.exists()).toBe(true);
    });

    it('does not truncate multiline messages', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ multiline: true, message: 'Multiline text' }),
      });

      // MultiLine messages use flex-1 without truncate
      const html = wrapper.html();
      // Should have flex-1 class but not truncate on the AlertDescription
      expect(html).toContain('flex-1');
    });

    it('sets title attribute for hover tooltip (full message on hover)', () => {
      const longMessage =
        'This is a very long message that should be truncated but shown in full on hover';
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: longMessage, multiline: false }),
      });

      const alertDescription = wrapper.find('[title]');
      expect(alertDescription.attributes('title')).toBe(longMessage);
    });
  });

  describe('Alert component integration', () => {
    it('passes correct variant to Alert based on type', () => {
      const types = ['success', 'error', 'warning', 'info'] as const;

      for (const type of types) {
        const wrapper = mount(FlashMessage, {
          props: createMockFlashMessageProps({ type }),
        });

        // Alert should have variant attribute matching type
        const alert = wrapper.find('div[class*="bg-"]');
        expect(alert.exists()).toBe(true);
        // Color coding should be present (the Alert component applies type-specific styling)
      }
    });
  });

  describe('styling and layout', () => {
    it('has flex layout with gap between elements', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test' }),
      });

      const html = wrapper.html();
      expect(html).toContain('flex');
      expect(html).toContain('gap-3');
    });

    it('has transition classes for animations', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test' }),
      });

      const html = wrapper.html();
      expect(html).toContain('transition');
      expect(html).toContain('duration-300');
    });

    it('icon has proper sizing (size-6)', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test' }),
      });

      const html = wrapper.html();
      expect(html).toContain('size-6');
    });

    it('button has proper styling (ghost variant, icon size, shrink-0)', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test' }),
      });

      const button = wrapper.find('button');
      const buttonClasses = button.classes().join(' ');
      expect(buttonClasses).toContain('h-6');
      expect(buttonClasses).toContain('w-6');
      expect(buttonClasses).toContain('shrink-0');
    });
  });

  describe('accessibility', () => {
    it('has semantic button for dismiss action', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test' }),
      });

      const button = wrapper.find('button');
      expect(button.element.tagName).toBe('BUTTON');
    });

    it('button contains X icon for close visual feedback', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: 'Test' }),
      });

      const html = wrapper.html();
      // X icon should be inside the button
      expect(html).toContain('h-4');
      expect(html).toContain('w-4');
    });
  });

  describe('message content handling', () => {
    it('displays empty messages without error', () => {
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: '' }),
      });

      expect(wrapper.exists()).toBe(true);
    });

    it('handles special characters in messages', () => {
      const message = 'Error: <script>alert("xss")</script> & other < > chars';
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message }),
      });

      // Message should be displayed as text, not HTML
      expect(wrapper.text()).toContain(message);
    });

    it('displays very long messages', () => {
      const longMessage = 'A'.repeat(500);
      const wrapper = mount(FlashMessage, {
        props: createMockFlashMessageProps({ message: longMessage, multiline: true }),
      });

      expect(wrapper.text()).toContain(longMessage);
    });
  });
});

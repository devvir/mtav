// Copilot - Pending review
import Badge from '@/components/badge/Badge.vue';
import { createMockBadgeProps } from '@/tests/helpers/fixtures';
import { mount } from '@/tests/helpers/mount';
import { describe, expect, it } from 'vitest';

describe('Badge', () => {
  let wrapper;

  describe('rendering', () => {
    it('renders slot content', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps(),
        slots: {
          default: 'Badge Text',
        },
      });

      expect(wrapper.text()).toContain('Badge Text');
    });

    it('renders with default variant', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ variant: 'default' }),
        slots: { default: 'Default' },
      });

      expect(wrapper.classes()).toContain('text-text');
    });

    it('renders with secondary variant', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ variant: 'secondary' }),
        slots: { default: 'Secondary' },
      });

      expect(wrapper.classes()).toContain('bg-surface');
    });

    it('renders with danger variant', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ variant: 'danger' }),
        slots: { default: 'Danger' },
      });

      expect(wrapper.classes().join(' ')).toMatch(/red|danger/i);
    });

    it('renders with success variant', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ variant: 'success' }),
        slots: { default: 'Success' },
      });

      expect(wrapper.classes().join(' ')).toMatch(/green|success/i);
    });

    it('renders with warning variant', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ variant: 'warning' }),
        slots: { default: 'Warning' },
      });

      expect(wrapper.classes().join(' ')).toMatch(/yellow|warning/i);
    });

    it('renders with info variant', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ variant: 'info' }),
        slots: { default: 'Info' },
      });

      expect(wrapper.classes().join(' ')).toMatch(/blue|info/i);
    });

    it('renders with outline variant', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ variant: 'outline' }),
        slots: { default: 'Outline' },
      });

      expect(wrapper.classes()).toContain('bg-background');
    });
  });

  describe('sizes', () => {
    it('renders extra small size', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ size: 'xs' }),
        slots: { default: 'XS' },
      });

      expect(wrapper.classes()).toContain('px-1.5');
    });

    it('renders small size', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ size: 'sm' }),
        slots: { default: 'SM' },
      });

      expect(wrapper.classes()).toContain('px-2');
    });

    it('renders medium size (default)', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ size: 'md' }),
        slots: { default: 'MD' },
      });

      expect(wrapper.classes()).toContain('px-2.5');
    });

    it('renders large size', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ size: 'lg' }),
        slots: { default: 'LG' },
      });

      expect(wrapper.classes()).toContain('px-3');
    });
  });

  describe('styling', () => {
    it('applies custom CSS classes', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps({ class: 'custom-class' }),
        slots: { default: 'Styled' },
      });

      expect(wrapper.classes()).toContain('custom-class');
    });

    it('always has rounded-full class', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps(),
        slots: { default: 'Rounded' },
      });

      expect(wrapper.classes()).toContain('rounded-full');
    });

    it('always has font-medium class', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps(),
        slots: { default: 'Bold' },
      });

      expect(wrapper.classes()).toContain('font-medium');
    });
  });

  describe('rendering as span', () => {
    it('renders as inline span element', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps(),
        slots: { default: 'Span' },
      });

      expect(wrapper.element.tagName).toBe('SPAN');
    });

    it('has inline-flex display', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps(),
        slots: { default: 'Flex' },
      });

      expect(wrapper.classes()).toContain('inline-flex');
    });

    it('items are vertically centered', () => {
      wrapper = mount(Badge, {
        props: createMockBadgeProps(),
        slots: { default: 'Centered' },
      });

      expect(wrapper.classes()).toContain('items-center');
    });
  });
});

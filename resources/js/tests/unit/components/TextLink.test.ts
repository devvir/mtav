// Copilot - Pending review
import TextLink from '@/components/TextLink.vue';
import { mount } from '@/tests/helpers/mount';
import { describe, expect, it } from 'vitest';

describe('TextLink', () => {
  let wrapper;

  describe('rendering', () => {
    it('renders link element', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Link Text' },
      });

      expect(wrapper.find('a').exists()).toBe(true);
    });

    it('renders link text from slot', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Click Me' },
      });

      expect(wrapper.text()).toContain('Click Me');
    });

    it('sets href attribute', () => {
      wrapper = mount(TextLink, {
        props: { href: '/about' },
        slots: { default: 'About' },
      });

      expect(wrapper.find('a').attributes('href')).toBe('/about');
    });
  });

  describe('link variations', () => {
    it('renders internal link', () => {
      wrapper = mount(TextLink, {
        props: { href: '/dashboard' },
        slots: { default: 'Dashboard' },
      });

      expect(wrapper.find('a').attributes('href')).toBe('/dashboard');
    });

    it('renders external link', () => {
      wrapper = mount(TextLink, {
        props: { href: 'https://example.com' },
        slots: { default: 'External' },
      });

      expect(wrapper.find('a').attributes('href')).toBe('https://example.com');
    });

    it('renders hash link', () => {
      wrapper = mount(TextLink, {
        props: { href: '#section' },
        slots: { default: 'Section' },
      });

      expect(wrapper.find('a').attributes('href')).toBe('#section');
    });

    it('renders mailto link', () => {
      wrapper = mount(TextLink, {
        props: { href: 'mailto:test@example.com' },
        slots: { default: 'Email' },
      });

      expect(wrapper.find('a').attributes('href')).toBe('mailto:test@example.com');
    });
  });

  describe('styling', () => {
    it('has text-based styling', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Link' },
      });

      const link = wrapper.find('a');
      expect(link.classes().length).toBeGreaterThan(0);
    });

    it('is visually a text link', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Link' },
      });

      const classes = wrapper.find('a').classes().join(' ');
      expect(classes).toMatch(/text|link|color/i);
    });

    it('likely has hover effects', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Hover' },
      });

      const classes = wrapper.find('a').classes().join(' ');
      expect(classes.length).toBeGreaterThan(0);
    });
  });

  describe('content variants', () => {
    it('renders link with icon', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: '<span class="icon">→</span> Link' },
      });

      expect(wrapper.text()).toContain('Link');
    });

    it('renders link with multiple words', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Very Long Link Text Here' },
      });

      expect(wrapper.text()).toBe('Very Long Link Text Here');
    });

    it('renders link with special characters', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Link & More' },
      });

      expect(wrapper.text()).toContain('Link & More');
    });
  });

  describe('element type', () => {
    it('is an anchor element', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Link' },
      });

      expect(wrapper.element.tagName).toBe('A');
    });
  });

  describe('accessibility', () => {
    it('has href for navigation', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Accessible Link' },
      });

      expect(wrapper.find('a').attributes('href')).toBeTruthy();
    });

    it('has readable text content', () => {
      wrapper = mount(TextLink, {
        props: { href: '/test' },
        slots: { default: 'Descriptive Link Text' },
      });

      expect(wrapper.text().length).toBeGreaterThan(0);
    });
  });

  describe('edge cases', () => {
    it('handles empty href', () => {
      wrapper = mount(TextLink, {
        props: { href: '' },
        slots: { default: 'Link' },
      });

      expect(wrapper.find('a').exists()).toBe(true);
    });

    it('handles root href', () => {
      wrapper = mount(TextLink, {
        props: { href: '/' },
        slots: { default: 'Home' },
      });

      expect(wrapper.find('a').attributes('href')).toBe('/');
    });
  });
});

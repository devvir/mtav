// Copilot - Pending review
import HeadingSmall from '@/components/HeadingSmall.vue';
import { mount } from '@/tests/helpers/mount';
import { describe, expect, it } from 'vitest';

describe('HeadingSmall', () => {
  describe('rendering', () => {
    it('renders heading text', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Small Heading' },
      });

      expect(wrapper.text()).toContain('Small Heading');
    });

    it('renders as h3 element', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Heading' },
      });

      expect(wrapper.find('h3').exists()).toBe(true);
    });

    it('renders in header element', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Semantic' },
      });

      expect(wrapper.find('header').exists()).toBe(true);
    });

    it('displays description when provided', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Title', description: 'Description text' },
      });

      expect(wrapper.text()).toContain('Description text');
    });
  });

  describe('typography', () => {
    it('renders as h3 not h1', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Small' },
      });

      expect(wrapper.find('h3').exists()).toBe(true);
      expect(wrapper.find('h1').exists()).toBe(false);
    });

    it('has font-weight styling on h3', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Weighted' },
      });

      const h3 = wrapper.find('h3');
      expect(h3.classes().join(' ')).toMatch(/font-medium|font-semibold|font-bold/);
    });

    it('applies text-xl sizing', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Sized' },
      });

      const h3 = wrapper.find('h3');
      expect(h3.classes()).toContain('text-xl');
    });
  });

  describe('styling', () => {
    it('applies h3 styling classes', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Styled' },
      });

      const h3 = wrapper.find('h3');
      const classStr = h3.classes().join(' ');
      expect(classStr.length).toBeGreaterThan(0);
      expect(classStr).toMatch(/text-text|font-medium/);
    });

    it('has margin-bottom on h3', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Margin' },
      });

      const h3 = wrapper.find('h3');
      expect(h3.classes()).toContain('mb-1');
    });

    it('has muted text styling on description', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Title', description: 'Desc' },
      });

      const p = wrapper.find('p');
      expect(p.classes()).toContain('text-text-muted');
    });
  });

  describe('props', () => {
    it('accepts title prop', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Test Title' },
      });

      expect(wrapper.props('title')).toBe('Test Title');
    });

    it('accepts optional description prop', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Title', description: 'Desc' },
      });

      expect(wrapper.props('description')).toBe('Desc');
    });
  });

  describe('accessibility', () => {
    it('is semantic h3 heading', () => {
      const wrapper = mount(HeadingSmall, {
        props: { title: 'Semantic' },
      });

      expect(wrapper.find('h3').exists()).toBe(true);
      expect(wrapper.element.querySelector('h3')).toBeTruthy();
    });
  });
});

// Copilot - Pending review
import { describe, it, expect } from 'vitest';
import { mount } from '@/tests/helpers/mount';
import Heading from '@/components/Heading.vue';

describe('Heading', () => {
  describe('rendering', () => {
    it('renders title text', () => {
      const wrapper = mount(Heading, {
        props: { title: 'Test Title' },
      });

      expect(wrapper.text()).toContain('Test Title');
    });

    it('renders title in h2 tag', () => {
      const wrapper = mount(Heading, {
        props: { title: 'Main Title' },
      });

      expect(wrapper.find('h2').exists()).toBe(true);
      expect(wrapper.find('h2').text()).toBe('Main Title');
    });

    it('renders description when provided', () => {
      const wrapper = mount(Heading, {
        props: {
          title: 'Title',
          description: 'Description Text',
        },
      });

      expect(wrapper.text()).toContain('Description Text');
    });

    it('does not render description when not provided', () => {
      const wrapper = mount(Heading, {
        props: { title: 'Title' },
      });

      expect(wrapper.findAll('p').length).toBe(0);
    });
  });

  describe('styling', () => {
    it('applies container styling classes', () => {
      const wrapper = mount(Heading, {
        props: { title: 'Styled' },
      });

      const div = wrapper.find('div');
      expect(div.classes()).toContain('mb-8');
      expect(div.classes()).toContain('space-y-0.5');
    });

    it('applies h2 typography classes', () => {
      const wrapper = mount(Heading, {
        props: { title: 'Typography' },
      });

      const h2 = wrapper.find('h2');
      expect(h2.classes()).toContain('text-xl');
      expect(h2.classes()).toContain('font-semibold');
    });

    it('applies description muted text classes', () => {
      const wrapper = mount(Heading, {
        props: { title: 'Title', description: 'Desc' },
      });

      const p = wrapper.find('p');
      expect(p.classes()).toContain('text-sm');
      expect(p.classes()).toContain('text-text-muted');
    });
  });

  describe('props', () => {
    it('accepts title prop', () => {
      const wrapper = mount(Heading, {
        props: { title: 'Custom' },
      });

      expect(wrapper.props('title')).toBe('Custom');
    });

    it('accepts optional description prop', () => {
      const wrapper = mount(Heading, {
        props: { title: 'Title', description: 'Desc' },
      });

      expect(wrapper.props('description')).toBe('Desc');
    });
  });
});

// Copilot - Pending review
import FormInput from '@/components/forms/FormInput.vue';
import { createMockFormInputProps } from '@/tests/helpers/fixtures';
import { mount } from '@/tests/helpers/mount';
import { describe, expect, it } from 'vitest';

describe('FormInput', () => {
  let wrapper;

  describe('basic rendering', () => {
    it('renders input element', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps(),
      });

      expect(wrapper.find('input').exists()).toBe(true);
    });

    it('has correct name attribute', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ name: 'email' }),
      });

      expect(wrapper.find('input').attributes('name')).toBe('email');
    });

    it('has correct type by default', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'text' }),
      });

      expect(wrapper.find('input').attributes('type')).toBe('text');
    });
  });

  describe('input types', () => {
    it('renders text input', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'text' }),
      });

      expect(wrapper.find('input[type="text"]').exists()).toBe(true);
    });

    it('renders email input', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'email' }),
      });

      expect(wrapper.find('input[type="email"]').exists()).toBe(true);
    });

    it('renders password input', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'password' }),
      });

      expect(wrapper.find('input[type="password"]').exists()).toBe(true);
    });

    it('renders number input', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'number' }),
      });

      expect(wrapper.find('input[type="number"]').exists()).toBe(true);
    });

    it('renders date input', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'date' }),
      });

      expect(wrapper.find('input[type="date"]').exists()).toBe(true);
    });

    it('handles hidden input gracefully', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'hidden' }),
      });

      const hiddenInput = wrapper.find('input[type="hidden"]');
      expect(hiddenInput.exists()).toBe(true);
    });
  });

  describe('disabled state', () => {
    it('is not disabled by default', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps(),
      });

      expect(wrapper.find('input').attributes('disabled')).toBeUndefined();
    });

    it('is disabled when disabled prop is true', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ disabled: true }),
      });

      expect(wrapper.find('input').attributes('disabled')).toBeDefined();
    });
  });

  describe('required state', () => {
    it('is not required by default', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps(),
      });

      expect(wrapper.find('input').attributes('required')).toBeUndefined();
    });

    it('has required attribute when required prop is true', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ required: true }),
      });

      expect(wrapper.find('input').attributes('required')).toBeDefined();
    });
  });

  describe('placeholder', () => {
    it('has placeholder attribute', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ placeholder: 'Enter text...' }),
      });

      expect(wrapper.find('input').attributes('placeholder')).toBe('Enter text...');
    });

    it('has empty placeholder by default', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ placeholder: '' }),
      });

      expect(wrapper.find('input').attributes('placeholder')).toBe('');
    });
  });

  describe('constraints', () => {
    it('has minlength attribute', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ minlength: 5 }),
      });

      expect(wrapper.find('input').attributes('minlength')).toBe('5');
    });

    it('has maxlength attribute', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ maxlength: 255 }),
      });

      expect(wrapper.find('input').attributes('maxlength')).toBe('255');
    });

    it('has min attribute for number inputs', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'number', min: 0 }),
      });

      expect(wrapper.find('input').attributes('min')).toBe('0');
    });

    it('has max attribute for number inputs', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ type: 'number', max: 100 }),
      });

      expect(wrapper.find('input').attributes('max')).toBe('100');
    });
  });

  describe('aria attributes', () => {
    it('has aria-label with label text', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ label: 'Email Address' }),
      });

      expect(wrapper.find('input').attributes('aria-label')).toBe('Email Address');
    });
  });

  describe('styling', () => {
    it('has bg-transparent class', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps(),
      });

      expect(wrapper.find('input').classes()).toContain('bg-transparent');
    });

    it('has text-text class', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps(),
      });

      expect(wrapper.find('input').classes()).toContain('text-text');
    });

    it('has outline-0 class', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps(),
      });

      expect(wrapper.find('input').classes()).toContain('outline-0');
    });
  });

  describe('autocomplete', () => {
    it('has autocomplete on when true', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ autocomplete: true }),
      });

      expect(wrapper.find('input').attributes('autocomplete')).toBe('on');
    });

    it('has autocomplete off when false', () => {
      wrapper = mount(FormInput, {
        props: createMockFormInputProps({ autocomplete: false }),
      });

      expect(wrapper.find('input').attributes('autocomplete')).toBe('off');
    });
  });
});

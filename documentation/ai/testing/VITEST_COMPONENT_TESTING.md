<!-- Copilot - Pending review -->
# Vitest Component Testing Guide

**Purpose**: Quick reference for writing unit/component tests for Vue components in MTAV using Vitest
**Target**: Frontend component testing (NOT E2E - see E2E.md for Playwright tests)
**Last Updated**: December 2025

---

## Quick Start

### File Structure
```
resources/js/
├── components/
│   └── SomeComponent.vue
├── __tests__/
│   └── unit/
│       └── SomeComponent.test.ts
```

### Basic Test Template
```typescript
import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import SomeComponent from '@/components/SomeComponent.vue';

describe('SomeComponent', () => {
  let wrapper;

  beforeEach(() => {
    wrapper = mount(SomeComponent, {
      props: { /* test props */ },
      global: {
        stubs: { /* stub child components */ },
      },
    });
  });

  it('renders component', () => {
    expect(wrapper.exists()).toBe(true);
  });

  it('emits event on user action', async () => {
    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted('some-event')).toBeTruthy();
  });
});
```

---

## Setup & Configuration

### Vitest Config
**File**: `vitest.config.ts`

```typescript
export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      globals: true,
      clearMocks: true,
      projects: [
        {
          extends: true,
          test: {
            name: 'unit',
            include: ['resources/js/tests/unit/**'],
            environment: 'node',
          },
        },
        {
          extends: true,
          test: {
            name: 'dom',
            include: ['resources/js/tests/dom/**'],
            environment: 'jsdom',
          },
        },
      ],
    },
  })
);
```

**Key Points:**
- `unit/` tests run in `node` environment (no DOM)
- `dom/` tests run in `jsdom` environment (DOM available)
- Component tests should use `dom` environment

### Running Tests
```bash
# Run all tests
npm run test

# Run specific test file
npm run test -- SomeComponent.test.ts

# Run with UI
npm run test -- --ui

# Watch mode
npm run test -- --watch
```

---

## Available Libraries

### @vue/test-utils
Vue component testing utilities.

**Key Methods:**
- `mount(Component, options)` - Mount component with options
- `wrapper.find(selector)` - Query element
- `wrapper.findComponent(Component)` - Query child component
- `wrapper.vm` - Access component instance
- `wrapper.props()` - Get all props
- `wrapper.emitted()` - Get emitted events
- `await wrapper.trigger('event')` - Trigger user action

### @vitest/ui
Visual test runner UI.

```bash
npm run test -- --ui
```

Opens browser at `http://localhost:51204/` with test results.

### jsdom
DOM environment for component rendering.

---

## Testing Patterns

### 1. Props & Rendering

**Test that component renders with correct props:**

```typescript
it('renders title from props', () => {
  wrapper = mount(SomeComponent, {
    props: {
      title: 'Test Title',
      description: 'Test Description',
    },
  });

  expect(wrapper.text()).toContain('Test Title');
  expect(wrapper.text()).toContain('Test Description');
});

it('applies conditional CSS classes from props', () => {
  wrapper = mount(SomeComponent, {
    props: { variant: 'error' },
  });

  expect(wrapper.classes()).toContain('error');
});
```

### 2. User Interactions

**Test that user actions trigger correct behavior:**

```typescript
it('emits submit event when button clicked', async () => {
  wrapper = mount(SomeComponent);

  await wrapper.find('button[type="submit"]').trigger('click');

  expect(wrapper.emitted('submit')).toBeTruthy();
  expect(wrapper.emitted('submit').length).toBe(1);
});

it('updates internal state on input', async () => {
  wrapper = mount(SomeComponent);

  await wrapper.find('input').setValue('new value');

  expect(wrapper.vm.value).toBe('new value');
});

it('calls method on checkbox change', async () => {
  wrapper = mount(SomeComponent);

  const checkbox = wrapper.find('input[type="checkbox"]');
  await checkbox.setValue(true);

  expect(wrapper.vm.isChecked).toBe(true);
});
```

### 3. Conditional Rendering

**Test that content shows/hides based on conditions:**

```typescript
it('shows error message when error prop is true', () => {
  wrapper = mount(SomeComponent, {
    props: { hasError: false },
  });

  expect(wrapper.find('.error-message').exists()).toBe(false);

  await wrapper.setProps({ hasError: true });

  expect(wrapper.find('.error-message').exists()).toBe(true);
});

it('hides button when disabled prop is true', () => {
  wrapper = mount(SomeComponent, {
    props: { disabled: true },
  });

  expect(wrapper.find('button').attributes('disabled')).toBeDefined();
});
```

### 4. v-model Binding

**Test two-way data binding:**

```typescript
it('updates v-model when input changes', async () => {
  wrapper = mount(SomeComponent, {
    props: {
      modelValue: 'initial',
      'onUpdate:modelValue': (v) => wrapper.setProps({ modelValue: v }),
    },
  });

  await wrapper.find('input').setValue('updated');

  expect(wrapper.emitted('update:modelValue')).toBeTruthy();
  expect(wrapper.emitted('update:modelValue')[0]).toEqual(['updated']);
});
```

### 5. Slot Content

**Test that slots render properly:**

```typescript
it('renders slot content', () => {
  wrapper = mount(SomeComponent, {
    slots: {
      default: '<p>Slot content</p>',
    },
  });

  expect(wrapper.text()).toContain('Slot content');
});

it('renders named slots', () => {
  wrapper = mount(SomeComponent, {
    slots: {
      header: '<h1>Header</h1>',
      footer: '<p>Footer</p>',
    },
  });

  expect(wrapper.html()).toContain('<h1>Header</h1>');
  expect(wrapper.html()).toContain('<p>Footer</p>');
});
```

### 6. Child Components & Stubs

**Test parent-child communication:**

```typescript
it('passes props to child component', () => {
  wrapper = mount(ParentComponent, {
    props: { itemId: 123 },
    global: {
      stubs: {
        ChildComponent: { props: ['id'], template: '<div>{{ id }}</div>' },
      },
    },
  });

  const child = wrapper.findComponent({ name: 'ChildComponent' });
  expect(child.props('id')).toBe(123);
});

it('handles child component emit', async () => {
  wrapper = mount(ParentComponent, {
    global: {
      stubs: {
        ChildComponent: {
          emits: ['update'],
          template: '<button @click="$emit(\'update\', \'data\')">Click</button>',
        },
      },
    },
  });

  const child = wrapper.findComponent({ name: 'ChildComponent' });
  await child.trigger('click');

  // Check parent behavior
  expect(wrapper.vm.someState).toBe('updated');
});
```

### 7. Composables & Hooks

**Test components using composables:**

```typescript
import { useTranslations } from '@/composables/useTranslations';
import { vi } from 'vitest';

it('uses translation composable', () => {
  vi.mock('@/composables/useTranslations', () => ({
    useTranslations: () => ({
      _: (text) => `[${text}]`, // Mock translation function
    }),
  }));

  wrapper = mount(SomeComponent);

  expect(wrapper.text()).toContain('[Translated text]');
});
```

### 8. Async Operations

**Test async behavior:**

```typescript
it('loads data on mount', async () => {
  wrapper = mount(SomeComponent);

  // Wait for async operation
  await wrapper.vm.$nextTick();

  expect(wrapper.vm.isLoading).toBe(false);
  expect(wrapper.vm.data).toBeDefined();
});

it('shows loading state during fetch', async () => {
  wrapper = mount(SomeComponent);

  // Initially loading
  expect(wrapper.find('.loading').exists()).toBe(true);

  // Wait for completion
  await new Promise(resolve => setTimeout(resolve, 100));
  await wrapper.vm.$nextTick();

  expect(wrapper.find('.loading').exists()).toBe(false);
  expect(wrapper.find('.data').exists()).toBe(true);
});
```

### 9. Form Components

**Test form input components:**

```typescript
it('validates required field', async () => {
  wrapper = mount(FormInput, {
    props: {
      label: 'Email',
      required: true,
      type: 'email',
    },
  });

  const input = wrapper.find('input');
  await input.setValue('');
  await input.trigger('blur');

  expect(wrapper.vm.error).toBe('This field is required');
});

it('formats input value', async () => {
  wrapper = mount(FormPhoneInput, {
    props: { modelValue: '' },
  });

  await wrapper.find('input').setValue('1234567890');

  expect(wrapper.emitted('update:modelValue')[0]).toEqual(['(123) 456-7890']);
});
```

### 10. Component State

**Test component's internal state:**

```typescript
it('toggles sidebar expanded state', async () => {
  wrapper = mount(Sidebar);

  expect(wrapper.vm.isExpanded).toBe(true);

  await wrapper.find('button.toggle').trigger('click');

  expect(wrapper.vm.isExpanded).toBe(false);
  expect(wrapper.classes()).toContain('collapsed');
});
```

---

## Testing Priority

### High Priority (Test First)
1. **Form components** - Input validation, submission, error display
2. **Entity list pages** - Filtering, pagination, authorization
3. **Entity detail pages** - Data display, edit button visibility
4. **Authorization** - Feature visibility by user role
5. **Modals** - Open/close behavior, form submission in modals
6. **Lottery system** - Preference management, drag-and-drop

### Medium Priority (Test Second)
1. Navigation links
2. Flash messages
3. Theme switching (dark/light)
4. Responsive layouts (mobile vs desktop)
5. Accessibility (keyboard nav, contrast)

### Low Priority (Test as Needed)
1. Styling-only components
2. Simple wrapper components
3. UI-only state (non-user-facing)

---

## Mocking & Stubbing

### Mock Inertia Utilities

```typescript
import { vi } from 'vitest';

// Mock useForm
vi.mock('@inertiajs/vue3', () => ({
  useForm: () => ({
    data: {},
    errors: {},
    submit: vi.fn(),
  }),
  usePage: () => ({
    props: {
      auth: { user: { id: 1, email: 'test@example.com' } },
    },
  }),
  Link: { template: '<a><slot /></a>' },
}));
```

### Mock Composables

```typescript
// Mock useAuth
vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    iAmAdmin: { value: true },
    iAmMember: { value: false },
    currentUser: { value: { id: 1 } },
  }),
}));
```

### Stub Child Components

```typescript
wrapper = mount(ParentComponent, {
  global: {
    stubs: {
      ChildComponent: true, // Stub as generic component
      AnotherChild: { template: '<div>Stubbed</div>' }, // Custom stub
    },
  },
});
```

---

## Best Practices

### ✅ DO

- Test user-facing behavior (clicks, inputs, navigation)
- Test authorization (only admins see admin features)
- Test error states and validation
- Use descriptive test names
- Mock external dependencies (API calls, routing)
- Test one thing per test
- Keep tests focused and readable

### ❌ DON'T

- Test implementation details (internal methods, private state)
- Mock everything (defeats purpose of testing)
- Test styling directly (CSS is not JS logic)
- Create test-specific component variations
- Write tests that depend on other tests
- Use arbitrary delays (use `await wrapper.vm.$nextTick()`)

---

## Common Assertions

```typescript
// Existence
expect(wrapper.exists()).toBe(true);
expect(wrapper.find('.selector').exists()).toBe(true);

// Visibility
expect(wrapper.isVisible()).toBe(true);
expect(wrapper.classes()).toContain('visible');

// Text content
expect(wrapper.text()).toContain('Expected text');
expect(wrapper.find('h1').text()).toBe('Exact title');

// HTML
expect(wrapper.html()).toContain('<div>');
expect(wrapper.element.innerHTML).toContain('content');

// Attributes
expect(wrapper.attributes('href')).toBe('/path');
expect(wrapper.attributes('disabled')).toBeDefined();

// Props & Data
expect(wrapper.props('title')).toBe('Expected');
expect(wrapper.vm.isActive).toBe(true);

// Events
expect(wrapper.emitted('click')).toBeTruthy();
expect(wrapper.emitted('submit').length).toBe(1);
expect(wrapper.emitted('submit')[0]).toEqual([data]);

// Classes
expect(wrapper.classes()).toContain('active');
expect(wrapper.classes('error')).toBe(true);

// Form elements
expect(wrapper.find('input').element.value).toBe('test');
expect(wrapper.find('select').element.selectedIndex).toBe(1);
```

---

## Debugging Tips

### Console Logging
```typescript
it('debugs wrapper state', () => {
  wrapper = mount(SomeComponent, { props: { /* ... */ } });

  // Log component HTML
  console.log(wrapper.html());

  // Log component data
  console.log(wrapper.vm);

  // Log emitted events
  console.log(wrapper.emitted());
});
```

### Vitest UI
```bash
npm run test -- --ui
```

Opens browser with visual test runner - click on test to see details.

### Vue DevTools
DevTools plugin may not work in test environment, but `wrapper.vm` gives access to component instance.

---

## Example: Complete Form Component Test

```typescript
import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import FormInput from '@/components/forms/FormInput.vue';

describe('FormInput', () => {
  let wrapper;

  beforeEach(() => {
    wrapper = mount(FormInput, {
      props: {
        label: 'Email',
        type: 'email',
        required: true,
        modelValue: '',
      },
    });
  });

  it('renders label and input', () => {
    expect(wrapper.find('label').text()).toBe('Email');
    expect(wrapper.find('input').attributes('type')).toBe('email');
  });

  it('updates v-model on input change', async () => {
    await wrapper.find('input').setValue('test@example.com');

    expect(wrapper.emitted('update:modelValue')).toBeTruthy();
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['test@example.com']);
  });

  it('shows error message when invalid', async () => {
    wrapper = mount(FormInput, {
      props: {
        label: 'Email',
        type: 'email',
        modelValue: 'invalid',
        error: 'Invalid email format',
      },
    });

    expect(wrapper.find('.error').text()).toBe('Invalid email format');
    expect(wrapper.classes()).toContain('has-error');
  });

  it('is disabled when disabled prop is true', () => {
    wrapper = mount(FormInput, {
      props: { disabled: true, label: 'Email' },
    });

    expect(wrapper.find('input').attributes('disabled')).toBeDefined();
  });

  it('shows required indicator', () => {
    expect(wrapper.find('.required-indicator').exists()).toBe(true);
  });
});
```

---

## Next Steps

1. **Create test directory structure**: `resources/js/tests/dom/`
2. **Start with high-priority components**: Form components first
3. **Run tests with UI**: `npm run test -- --ui`
4. **Aim for meaningful coverage**: Not line coverage, but behavior coverage
5. **Refactor tests as needed**: They're code too

See [PHILOSOPHY.md](./PHILOSOPHY.md) for backend testing patterns (Pest).
See [E2E.md](./E2E.md) for end-to-end browser testing (Playwright).

# Vue Component Tests (Vitest)

## Running Tests

```bash
mtav vitest              # Run all tests (unit + feature)
mtav vitest -- --project unit
mtav vitest -- --project feature
mtav vitest -- --watch   # Watch mode
mtav vitest -- --ui      # Visual UI
```

## Test Organization

**`unit/`** - Fast, isolated tests (suitable for pre-commit hooks)
- `unit/components/` - Individual component tests
- `unit/composables/` - Composable tests
- `unit/utils/` - Utility function tests

**`feature/`** - Integration tests (multiple components together)
- `feature/forms/` - Form system integration
- `feature/flash/` - Flash message system
- `feature/navigation/` - Navigation components
- `feature/lottery/` - Lottery module
- etc.

## Writing a Test

### Unit Test (Single Component)

```typescript
import { describe, it, expect } from 'vitest'
import { mount } from '@/tests/helpers/mount'
import Component from '@/components/Component.vue'
import { createMockComponentProps } from '@/tests/helpers/fixtures'

describe('Component', () => {
  it('renders', () => {
    const wrapper = mount(Component, {
      props: createMockComponentProps()
    })
    expect(wrapper.exists()).toBe(true)
  })
})
```

### Feature Test (Multiple Components)

Same structure, but imports and tests multiple related components working together.

```typescript
describe('Form System', () => {
  it('renders form with input fields', () => {
    const wrapper = mount(Form, {
      props: {
        action: '/submit',
        type: 'create',
        title: 'Create Item',
        specs: { name: { element: 'input', type: 'text', value: '' } }
      }
    })
    expect(wrapper.findAll('input').length).toBeGreaterThan(0)
  })
})
```

## Available Helpers

```typescript
// Mount component with auto-mocks
import { mount, flushUpdates } from '@/tests/helpers/mount'

// Test data factories
import { createMock[ComponentName]Props } from '@/tests/helpers/fixtures'

// All automatically mocked:
// - usePage(), useForm() (Inertia)
// - useAuth(), useTranslations(), useDates(), useLocalState()
// - All UI components, icons, Dialog, Button, Input
// - localStorage, matchMedia
```

## File Structure

```
resources/js/tests/
├── setup.ts              # Global mocks
├── helpers/              # Helper utilities
│   ├── mount.ts
│   ├── inertia.ts
│   ├── composables.ts
│   └── fixtures.ts
├── mocks/
│   └── components.ts
├── unit/                 # Fast, isolated tests
│   ├── components/       # Individual component tests
│   ├── composables/      # Composable tests
│   └── utils/            # Utility function tests
└── feature/              # Integration tests
    ├── forms/
    ├── flash/
    ├── navigation/
    └── ...
```

## Configuration

- Config: `vitest.config.ts`
- Environment: jsdom for all tests
- Auto-imported: Vue, @vueuse/core, Inertia utilities
- TypeScript strict mode enabled

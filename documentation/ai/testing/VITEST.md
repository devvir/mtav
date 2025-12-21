<!-- Copilot - Pending review -->
# Vitest Component Testing

**Status**: 88 tests written for 6 components. Infrastructure ready.

## Quick Facts

- **Test command**: `mtav vitest` (runs in Docker)
- **Test files**: `resources/js/tests/dom/components/`
- **Watch mode**: `mtav vitest -- --watch`
- **UI mode**: `mtav vitest -- --ui`
- **Config**: `vitest.config.ts` loads `resources/js/tests/setup.ts`

## Test Helpers (Pre-Configured)

```typescript
import { mount, flushUpdates } from '@/tests/helpers/mount'
import { createMockBadgeProps, createMockFormInputProps, ... } from '@/tests/helpers/fixtures'

// Everything auto-mocked:
// - usePage(), useForm() (Inertia)
// - useAuth(), useTranslations(), useDates(), useLocalState()
// - All UI components, icons, Dialog, Button, Input, etc.
// - localStorage, matchMedia
```

## Writing a Test

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
    expect(wrapper.find('button').exists()).toBe(true)
  })
})
```

No additional setup needed. All mocks are automatic.

## Current Coverage

| Component | Tests |
|-----------|-------|
| Badge | 14 |
| Heading | 9 |
| FormInput | 20 |
| FlashMessage | 15 |
| HeadingSmall | 8 |
| TextLink | 17 |
| Setup verification | 5 |
| **Total** | **88** |

## Infrastructure Files

```
resources/js/tests/
├── setup.ts                 # Global mocks
├── helpers/
│   ├── mount.ts            # Smart mount wrapper
│   ├── inertia.ts          # Inertia mocks
│   ├── composables.ts      # Composable mocks
│   └── fixtures.ts         # Test data factories
└── mocks/
    └── components.ts       # UI component stubs
```

## Notes

- Tests run in jsdom environment with global mocks pre-applied
- All Vue auto-imports work in tests
- TypeScript strict mode enabled
- Docker integration automatic via `mtav vitest`
- Tests can be extended indefinitely using same patterns

// Copilot - Pending review
import { vi } from 'vitest';

/**
 * Mock Inertia utilities (usePage, useForm)
 */
export function mockInertia(props: Record<string, any> = {}, auth: Record<string, any> = {}) {
  const pageProps = {
    auth: {
      user: {
        id: 1,
        email: 'test@example.com',
        name: 'Test User',
        is_admin: false,
        ...auth,
      },
    },
    ...props,
  };

  vi.mock(
    '@inertiajs/vue3',
    () => ({
      useForm: vi.fn((data) => ({
        data: () => data,
        ...data,
        errors: {},
        processing: false,
        isDirty: false,
        submit: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        get: vi.fn(),
        reset: vi.fn(),
        setData: vi.fn(),
        clearErrors: vi.fn(),
        setError: vi.fn(),
        transform: vi.fn(),
      })),
      usePage: vi.fn(() => ({
        props: pageProps,
        url: '/',
        component: 'Test',
      })),
      router: {
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        get: vi.fn(),
        visit: vi.fn(),
      },
      Link: {
        template: '<a><slot /></a>',
      },
    }),
    { virtual: true },
  );
}

/**
 * Create a mock form object for testing
 */
export function createMockForm(data: Record<string, any> = {}) {
  const form = {
    ...data,
    errors: {},
    processing: false,
    isDirty: false,
    submit: vi.fn(),
    post: vi.fn().mockResolvedValue({}),
    put: vi.fn().mockResolvedValue({}),
    patch: vi.fn().mockResolvedValue({}),
    delete: vi.fn().mockResolvedValue({}),
    get: vi.fn().mockResolvedValue({}),
    reset: vi.fn(),
    setData: vi.fn(),
    clearErrors: vi.fn(),
    setError: vi.fn((field: string, message: string) => {
      form.errors[field] = message;
    }),
    transform: vi.fn(),
  };

  return form;
}

/**
 * Create mock page props
 */
export function createMockPageProps(overrides: Record<string, any> = {}) {
  return {
    auth: {
      user: {
        id: 1,
        email: 'test@example.com',
        name: 'Test User',
        is_admin: false,
      },
    },
    ...overrides,
  };
}

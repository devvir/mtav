// Copilot - Pending review
import { vi } from 'vitest';

/**
 * Global test setup
 * Runs once before all tests
 */

// ============================================================================
// MOCKS - MUST BE AT MODULE LEVEL (not in functions)
// ============================================================================

// Mock Inertia
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
      props: {
        auth: {
          user: {
            id: 1,
            email: 'test@example.com',
            name: 'Test User',
            is_admin: false,
          },
        },
      },
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

// Mock useTranslations
vi.mock(
  '@/composables/useTranslations',
  () => {
    const mockTranslate = (text: string) => text;
    return {
      useTranslations: () => ({
        _: mockTranslate,
        locale: 'en',
        setLocale: vi.fn(),
      }),
      useTranslation: () => ({
        _: mockTranslate,
        trans: mockTranslate,
        locale: { value: 'en' },
        translations: {},
        setLocale: vi.fn(),
      }),
      _: mockTranslate,
      trans: mockTranslate,
      locale: { value: 'en' },
      translations: {},
      setLocale: vi.fn(),
    };
  },
  { virtual: true },
);

// Mock useAuth
vi.mock(
  '@/composables/useAuth',
  () => ({
    useAuth: () => ({
      iAmAdmin: { value: false },
      iAmMember: { value: true },
      iAmSuperadmin: { value: false },
      currentUser: {
        value: {
          id: 1,
          email: 'test@example.com',
          name: 'Test User',
          is_admin: false,
        },
      },
      can: {
        create: vi.fn(() => true),
        viewAny: vi.fn(() => true),
      },
    }),
  }),
  { virtual: true },
);

// Mock useDates
vi.mock(
  '@/composables/useDates',
  () => ({
    useDates: () => ({
      fromUTC: (date: string) => date,
      toUTC: (date: string) => date,
      formatDate: (date: string) => date,
    }),
    fromUTC: (date: string) => date,
    toUTC: (date: string) => date,
  }),
  { virtual: true },
);

// Mock useLocalState
vi.mock(
  '@/composables/useLocalState',
  () => ({
    useLocalState: (key: string, defaultValue: any = null) => {
      return { value: defaultValue };
    },
  }),
  { virtual: true },
);

// Mock useCsrfToken
vi.mock(
  '@/composables/useCsrfToken',
  () => ({
    useCsrfToken: () => ({
      token: 'mock-csrf-token',
      autoRefreshCsrfToken: vi.fn(),
    }),
  }),
  { virtual: true },
);
// ============================================================================
// DOM MOCKS
// ============================================================================

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};

  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString();
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    clear: () => {
      store = {};
    },
  };
})();

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
});

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Copilot - Pending review
import { vi } from 'vitest';

/**
 * Mock test data fixtures
 */

export function createMockBadgeProps(overrides: Record<string, any> = {}) {
  return {
    variant: 'default',
    size: 'md',
    ...overrides,
  };
}

export function createMockFormInputProps(overrides: Record<string, any> = {}) {
  return {
    name: 'test-field',
    label: 'Test Label',
    type: 'text',
    required: false,
    disabled: false,
    placeholder: '',
    ...overrides,
  };
}

export function createMockFormSelectProps(overrides: Record<string, any> = {}) {
  return {
    name: 'test-select',
    label: 'Select Option',
    options: {
      1: 'Option 1',
      2: 'Option 2',
      3: 'Option 3',
    },
    required: false,
    disabled: false,
    ...overrides,
  };
}

export function createMockFlashMessageProps(overrides: Record<string, any> = {}) {
  return {
    type: 'success',
    message: 'Test message',
    multiline: false,
    ...overrides,
  };
}

export function createMockConfirmationModalProps(overrides: Record<string, any> = {}) {
  return {
    open: true,
    title: 'Confirm Action',
    description: 'Are you sure?',
    expectedText: 'confirm',
    confirmButtonText: 'Delete',
    variant: 'destructive',
    ...overrides,
  };
}

export function createMockHeadingProps(overrides: Record<string, any> = {}) {
  return {
    title: 'Test Heading',
    subtitle: undefined,
    icon: undefined,
    ...overrides,
  };
}

export function createMockCardProps(overrides: Record<string, any> = {}) {
  return {
    title: 'Card Title',
    ...overrides,
  };
}

export function createMockUser(overrides: Record<string, any> = {}) {
  return {
    id: 1,
    email: 'test@example.com',
    name: 'Test User',
    firstname: 'Test',
    lastname: 'User',
    is_admin: false,
    ...overrides,
  };
}

export function createMockProject(overrides: Record<string, any> = {}) {
  return {
    id: 1,
    name: 'Test Project',
    description: 'Test project description',
    organization: 'Test Org',
    ...overrides,
  };
}

export function createMockFamily(overrides: Record<string, any> = {}) {
  return {
    id: 1,
    name: 'Test Family',
    project_id: 1,
    unit_type_id: 1,
    ...overrides,
  };
}

/**
 * Sleep utility for time-based tests
 */
export function sleep(ms: number = 100) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Mock timers for testing timeouts and intervals
 */
export function mockTimers() {
  const timers = vi.useFakeTimers();
  return {
    timers,
    runAll: () => timers.runAllTimers(),
    advance: (ms: number) => timers.advanceTimersByTime(ms),
    cleanup: () => timers.restoreAllMocks(),
  };
}

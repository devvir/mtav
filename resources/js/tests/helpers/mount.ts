// Copilot - Pending review
import { mount as vtuMount, flushPromises } from '@vue/test-utils';
import type { Component } from 'vue';
import { vi } from 'vitest';
import { mockInertia } from './inertia';
import { mockUIComponents } from '../mocks/components';

export interface MountOptions {
  props?: Record<string, any>;
  slots?: Record<string, any>;
  global?: {
    mocks?: Record<string, any>;
    stubs?: Record<string, any>;
  };
  inertiaProps?: Record<string, any>;
  auth?: Record<string, any>;
}

/**
 * Smart mount wrapper that sets up common mocks and stubs
 * Reduces boilerplate for component tests
 */
export function mount(component: Component, options: MountOptions = {}) {
  const {
    props = {},
    slots = {},
    global = {},
    inertiaProps = {},
    auth = {},
  } = options;

  // Set up Inertia mocks
  mockInertia(inertiaProps, auth);

  return vtuMount(component, {
    props,
    slots,
    global: {
      mocks: {
        ...global.mocks,
      },
      stubs: {
        Link: { template: '<a><slot /></a>' },
        ...mockUIComponents(),
        ...global.stubs,
      },
      plugins: [
        {
          install: (app) => {
            // Make sure auto-imports still work
            app.config.globalProperties.$t = (key: string) => key;
          },
        },
      ],
    },
  });
}

/**
 * Flush promises and Vue updates
 */
export async function flushUpdates() {
  await flushPromises();
  return new Promise(resolve => setTimeout(resolve, 0));
}

/**
 * Wait for element to appear
 */
export async function waitForElement(wrapper: any, selector: string) {
  const maxAttempts = 50;
  for (let i = 0; i < maxAttempts; i++) {
    await flushUpdates();
    if (wrapper.find(selector).exists()) {
      return wrapper.find(selector);
    }
  }
  throw new Error(`Element "${selector}" not found after ${maxAttempts} attempts`);
}

/**
 * Emit and wait for updates
 */
export async function emitAndWait(wrapper: any, selector: string, event: string) {
  await wrapper.find(selector).trigger(event);
  await flushUpdates();
}

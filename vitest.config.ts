import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config';

/**
 * Frontend logic that is worth testing on its own (geometry, queues, components with
 * behaviour). Pages are covered by the browser suite (tests/Browser).
 */
export default defineConfig((configEnv) =>
  mergeConfig(viteConfig(configEnv), {
    test: {
      globals: true,
      clearMocks: true,
      environment: 'jsdom',
      include: ['resources/js/tests/**/*.test.{ts,tsx}'],
      setupFiles: ['resources/js/tests/setup.ts'],
    },
  }),
);

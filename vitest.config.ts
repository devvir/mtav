import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config';

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
  }),
);

import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config';

export default defineConfig((configEnv) => mergeConfig(
  viteConfig(configEnv),
  {
    test: {
      globals: true,
      clearMocks: true,
      setupFiles: ['resources/js/tests/setup.ts'],
      projects: [
        {
          extends: true,
          test: {
            name: 'unit',
            include: ['resources/js/tests/unit/**'],
            environment: 'jsdom',
          },
        },
        {
          extends: true,
          test: {
            name: 'feature',
            include: ['resources/js/tests/feature/**'],
            environment: 'jsdom',
          },
        },
      ],
    },
  },
));

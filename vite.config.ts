import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');

  return {
    plugins: [
      // Not under Vitest: the plugin deletes public/hot when its process exits, and
      // every environment shares it (a test run would cut a running dev server off)
      !process.env.VITEST &&
        laravel({
          input: ['resources/js/app.tsx'],
          refresh: true,
        }),
      react(),
    ],

    resolve: {
      alias: {
        '@': path.resolve(import.meta.dirname, './resources/js'),
        '@lang': path.resolve(import.meta.dirname, './lang'),
      },
    },

    server: {
      host: '0.0.0.0',
      port: 5173,
      hmr: {
        host: env.VITE_HMR_HOST || 'localhost',
      },
      // File events reach the container through the bind mount on Linux; polling (for
      // hosts where they do not: VITE_WATCH_POLLING=true) costs whole CPU cores. Either
      // way, only the sources are watched.
      watch: {
        usePolling: env.VITE_WATCH_POLLING === 'true',
        interval: 1000,
        ignored: [
          '**/node_modules/**',
          '**/vendor/**',
          '**/storage/**',
          '**/public/build/**',
          '**/.git/**',
          '**/tests/**',
          '**/.pnpm-store/**',
        ],
      },
    },
  };
});

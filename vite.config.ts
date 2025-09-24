import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import AutoImport from 'unplugin-auto-import/vite';
import { defineConfig } from 'vite';
import i18nPlugin from './resources/js/plugins/i18n';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.ts'],
      ssr: 'resources/js/ssr.ts',
      refresh: true,
    }),

    tailwindcss(),

    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),

    AutoImport({
      imports: [
        'vue',
        '@vueuse/core',
        { from: 'vue', imports: ['HTMLAttributes'], type: true },
        {
          '@inertiajs/vue3': ['useForm', 'usePage', 'router'],
        },
      ],
      dts: true,
      vueTemplate: true,
    }),

    i18nPlugin(),
  ],

  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
    },
  },

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
});

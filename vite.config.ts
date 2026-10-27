import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import AutoImport from 'unplugin-auto-import/vite';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');

  return {
    plugins: [
      // Not under Vitest: the plugin deletes public/hot when its process exits, and
      // every environment shares it (a test run would cut a running dev server off)
      !process.env.VITEST &&
        laravel({
          input: ['resources/js/app.ts'],
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
    },
  };
});

// The warden's page: built from web/ into dist/, which server.mjs serves. It is made of MTAV's own design system
// (resources/js/design), so it looks and behaves like the app; it never ships with it.
import react from '@vitejs/plugin-react';
import path from 'node:path';
import { defineConfig } from 'vite';

const repo = path.resolve(import.meta.dirname, '../..');

export default defineConfig({
  root: path.join(import.meta.dirname, 'web'),
  base: '/',
  plugins: [react()],
  resolve: {
    alias: { '@': path.join(repo, 'resources/js') },
  },
  build: {
    outDir: path.join(import.meta.dirname, 'dist'),
    emptyOutDir: true,
    chunkSizeWarningLimit: 2000,
  },
});

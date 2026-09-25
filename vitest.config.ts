import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['resources/js/__tests__/**/*.test.ts'],
    setupFiles: ['resources/js/__tests__/setup.ts'],
    alias: {
      '@': resolve(__dirname, './resources/js'),
    },
  },
});
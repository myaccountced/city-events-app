import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueJsx(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['src/tests/unit/**/*.{test,spec}.{js,ts}']
  },
  // allow this for manually testing facebook/x post
  // will need to change the allowed host based on what is generate by ngrok
  // server: {
  //   allowedHosts: ['a753-142-99-247-232.ngrok-free.app', 'localhost'],
  // },
})

import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [
      vue(),
      vueDevTools(),
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      },
    },
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      proxy: {
        '/api': {
          // When running via docker-compose, the dev server runs inside the
          // frontend container, so target should be the backend service name.
          target: env.VITE_BACKEND_URL || 'http://backend:8000',
          changeOrigin: true,
        },
        '/sanctum': {
          target: env.VITE_BACKEND_URL || 'http://backend:8000',
          changeOrigin: true,
        },
      },
    },
  }
})

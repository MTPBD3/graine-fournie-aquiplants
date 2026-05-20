import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: './src/test-setup.js',
  },
  plugins: [react()],

  server: {
    host: true,
    port: 3000,
    historyApiFallback: true,
    proxy: {
      '/api': {
        target: 'http://symfony_app:8000',
        changeOrigin: true,
      },
    },
  },

  // Pré-bundling accéléré en dev
  optimizeDeps: {
    include: [
      'react', 'react-dom', 'react-router-dom',
      '@mui/material', '@mui/icons-material',
      '@emotion/react', '@emotion/styled',
      'recharts',
    ],
  },

  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('@mui/icons-material')) return 'vendor-mui-icons';
            if (id.includes('@mui')) return 'vendor-mui';
            if (id.includes('recharts')) return 'vendor-recharts';
            if (id.includes('react')) return 'vendor-react';
          }
        },
      },
    },
  },
})

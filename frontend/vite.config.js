import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  publicDir: false,
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    chunkSizeWarningLimit: 700,
    rollupOptions: {
      output: {
        manualChunks: {
          react: ['react', 'react-dom', 'react-router-dom'],
          charts: ['apexcharts', 'react-apexcharts'],
          ui: ['lucide-react', 'framer-motion'],
          tables: ['@tanstack/react-table']
        }
      }
    }
  },
  server: {
    port: 5173,
    strictPort: false
  }
});

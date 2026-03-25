import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'statistics-vendor': ['react', 'react-dom', 'recharts'],
                },
            },
        },
    },
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/encuesta/formulario.js', 'resources/js/estadisticas/index.jsx'],
            refresh: true,
        }),
    ],
});

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
    plugins: [vue()],
    resolve: {
        extensions: ['.mjs', '.js', '.json', '.vue'],
    },
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        lib: {
            entry: 'resources/js/tool.js',
            formats: ['umd'],
            name: 'AegisTool',
            fileName: () => 'js/tool.js',
        },
        rollupOptions: {
            external: ['vue', 'laravel-nova', 'laravel-nova-ui', 'laravel-nova-util'],
            output: {
                globals: {
                    vue: 'Vue',
                    'laravel-nova': 'LaravelNova',
                    'laravel-nova-ui': 'LaravelNovaUi',
                    'laravel-nova-util': 'LaravelNovaUtil',
                },
                assetFileNames: (assetInfo) =>
                    assetInfo.names?.some((name) => name.endsWith('.css')) ? 'css/tool.css' : 'assets/[name][extname]',
            },
        },
    },
})

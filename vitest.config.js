import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

export default defineConfig({
    plugins: [vue()],
    resolve: {
        extensions: ['.mjs', '.js', '.json', '.vue'],
    },
    test: {
        environment: 'happy-dom',
        include: ['resources/js/**/*.spec.js'],
        coverage: {
            provider: 'v8',
            include: ['resources/js/**/*.{js,vue}'],
            exclude: ['resources/js/**/*.spec.js', 'resources/js/testing.js', 'resources/js/tool.js'],
            reporter: ['text', 'text-summary'],
            thresholds: { lines: 90, functions: 90, statements: 90, branches: 85 },
        },
    },
})

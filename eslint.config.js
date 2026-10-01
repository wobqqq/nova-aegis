import js from '@eslint/js'
import globals from 'globals'
import pluginVue from 'eslint-plugin-vue'
import prettier from 'eslint-config-prettier'

export default [
    { ignores: ['**/node_modules/**', '**/vendor/**', 'dist/**', 'coverage/**'] },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: { ...globals.browser, Nova: 'readonly', Vue: 'readonly' },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'error',
        },
    },
    {
        files: ['resources/js/**/*.spec.js'],
        languageOptions: { globals: { ...globals.node, ...globals.browser } },
    },
    { files: ['*.config.js'], languageOptions: { globals: globals.node } },
    prettier,
]

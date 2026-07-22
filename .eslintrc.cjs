/** @type {import('eslint').Linter.Config} */
module.exports = {
    root: true,
    env: {
        browser: true,
        es2022: true,
        node: true,
    },
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
    },
    plugins: ['vue'],
    rules: {
        // Vue-specific
        'vue/multi-word-component-names': 'off',   // Inertia page components use single words
        'vue/no-v-html': 'warn',
        'vue/component-name-in-template-casing': ['error', 'PascalCase'],
        'vue/html-indent': ['error', 4],
        'vue/max-attributes-per-line': ['error', {
            singleline: { max: 3 },
            multiline: { max: 1 },
        }],

        // General JS
        'no-console': ['warn', { allow: ['warn', 'error'] }],
        'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
        'prefer-const': 'error',
        'no-var': 'error',
        'eqeqeq': ['error', 'always', { null: 'ignore' }],
    },
};

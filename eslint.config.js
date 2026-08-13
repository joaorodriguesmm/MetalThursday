import js from '@eslint/js';
import globals from 'globals';
import {
    defineConfig,
    globalIgnores,
} from 'eslint/config';

/**
 * Ficheiros JavaScript controlados pelo projeto.
 *
 * @type {ReadonlyArray<string>}
 *
 * @since 2.0.0
 */
const FICHEIROS_JAVASCRIPT = Object.freeze([
    'eslint.config.js',
    'vite.config.js',
    'resources/js/**/*.js',
]);

/**
 * Configuração estática do ESLint para o JavaScript do projeto.
 *
 * @since 2.0.0
 */
export default defineConfig([
    globalIgnores(
        [
            'node_modules/',
            'public/build/',
            'storage/',
            'vendor/',
        ],
        'metal-thursday/ficheiros-ignorados',
    ),

    {
        name: 'metal-thursday/javascript',

        files: FICHEIROS_JAVASCRIPT,

        plugins: {
            js,
        },

        extends: [
            'js/recommended',
        ],

        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
        },

        linterOptions: {
            reportUnusedDisableDirectives: 'error',
            reportUnusedInlineConfigs: 'error',
        },

        rules: {
            'no-unused-vars': [
                'error',
                {
                    args: 'after-used',
                    argsIgnorePattern: '^_',
                    caughtErrors: 'all',
                    caughtErrorsIgnorePattern: '^_',
                    ignoreRestSiblings: true,
                },
            ],

            'no-console': [
                'error',
                {
                    allow: [
                        'warn',
                        'error',
                    ],
                },
            ],

            curly: [
                'error',
                'all',
            ],

            eqeqeq: [
                'error',
                'always',
            ],

            'no-duplicate-imports': 'error',
            'no-template-curly-in-string': 'error',
            'no-useless-concat': 'error',
            'no-var': 'error',

            'object-shorthand': [
                'error',
                'always',
            ],

            'prefer-const': 'error',
            radix: 'error',
        },
    },

    {
        name: 'metal-thursday/javascript-node',

        files: [
            'eslint.config.js',
            'vite.config.js',
        ],

        languageOptions: {
            globals: globals.nodeBuiltin,
        },
    },

    {
        name: 'metal-thursday/javascript-navegador',

        files: [
            'resources/js/**/*.js',
        ],

        languageOptions: {
            globals: globals.browser,
        },
    },
]);

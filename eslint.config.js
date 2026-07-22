import js from '@eslint/js';
import globals from 'globals';
import { defineConfig } from 'eslint/config';

/**
 * Configuração estática do ESLint para o JavaScript da aplicação.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
export default defineConfig([
    {
        name: 'metal-thursday/ficheiros-ignorados',

        ignores: [
            'node_modules/**',
            'public/build/**',
            'storage/**',
            'vendor/**',
        ],
    },

    {
        name: 'metal-thursday/javascript-navegador',

        files: [
            'resources/js/**/*.js',
        ],

        plugins: {
            js,
        },

        extends: [
            'js/recommended',
        ],

        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',

            globals: {
                ...globals.browser,
            },
        },

        linterOptions: {
            reportUnusedDisableDirectives: 'error',
            reportUnusedInlineConfigs: 'error',
        },

        rules: {
            /*
             * Impede variáveis declaradas mas nunca utilizadas. Nomes
             * iniciados por underscore podem ser usados para indicar
             * parâmetros intencionalmente ignorados.
             */
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

            /*
             * Evita mensagens de depuração esquecidas em produção, mantendo
             * apenas avisos e erros deliberados.
             */
            'no-console': [
                'error',
                {
                    allow: [
                        'warn',
                        'error',
                    ],
                },
            ],

            /*
             * Regras adicionais orientadas para correção e consistência
             * sem introduzir um formatador paralelo ao projeto.
             */
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
]);

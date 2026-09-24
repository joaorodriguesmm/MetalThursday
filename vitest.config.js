import { defineConfig } from 'vitest/config';

/**
 * Configuração dos testes JavaScript do projeto.
 *
 * @since 2.0.0
 */
export default defineConfig({
    test: {
        environment: 'jsdom',

        include: [
            'tests/JavaScript/**/*.test.js',
        ],
    },
});

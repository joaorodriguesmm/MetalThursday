import {
    defineConfig,
    devices,
} from '@playwright/test';

/**
 * Configuração dos testes de browser da aplicação.
 *
 * @since 2.0.0
 */
export default defineConfig({
    testDir: './tests/Browser',

    fullyParallel: false,

    forbidOnly: Boolean(
        process.env.CI,
    ),

    retries: process.env.CI
        ? 2
        : 0,

    workers: process.env.CI
        ? 1
        : undefined,

    reporter: 'line',

    use: {
        baseURL: 'http://127.0.0.1:8001',

        trace: 'retain-on-failure',
    },

    projects: [
        {
            name: 'chromium',

            use: {
                ...devices['Desktop Chrome'],
            },
        },
    ],

    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8001',

        url: 'http://127.0.0.1:8001/entrar',

        reuseExistingServer: ! process.env.CI,

        timeout: 120000,
    },
});

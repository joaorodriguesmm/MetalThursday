/**
 * Exporta a configuração do Vite para o projeto.
 *
 * @since 1.0
 * @version 1.0
 */
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // CSS
                'resources/sass/app.scss',
                // JS
                'resources/js/app.js',
                'resources/js/pages/createMetalThursday.js',
                'resources/js/pages/editMetalThursday.js',
                'resources/js/pages/entities.js',
                'resources/js/pages/forgotPassword.js',
                'resources/js/pages/home.js',
                'resources/js/pages/inviteRegister.js',
                'resources/js/pages/login.js',
                'resources/js/pages/profile.js',
                'resources/js/pages/resetPassword.js',
                'resources/js/pages/showMetalThursday.js',
            ],
            refresh: true,
        }),
    ],
});

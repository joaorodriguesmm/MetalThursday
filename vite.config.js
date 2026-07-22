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
                'resources/js/paginas/recuperarPalavraPasse.js',
                'resources/js/pages/home.js',
                'resources/js/paginas/registoConvite.js',
                'resources/js/paginas/iniciarSessao.js',
                'resources/js/paginas/perfil.js',
                'resources/js/paginas/redefinirPalavraPasse.js',
                'resources/js/pages/showMetalThursday.js',
            ],
            refresh: true,
        }),
    ],
});

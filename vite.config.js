import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Entradas de estilos e scripts processadas pelo Vite.
 *
 * @type {ReadonlyArray<string>}
 *
 * @since 1.0.0
 */
const ENTRADAS_APLICACAO = Object.freeze([
    'resources/sass/app.scss',

    'resources/js/app.js',

    'resources/js/paginas/criarMetalThursday.js',
    'resources/js/paginas/detalhesMetalThursday.js',
    'resources/js/paginas/editarMetalThursday.js',
    'resources/js/paginas/entidades.js',
    'resources/js/paginas/iniciarSessao.js',
    'resources/js/paginas/inicio.js',
    'resources/js/paginas/perfil.js',
    'resources/js/paginas/perfilArtista.js',
    'resources/js/paginas/recuperarPalavraPasse.js',
    'resources/js/paginas/redefinirPalavraPasse.js',
    'resources/js/paginas/registoConvite.js',
]);

/**
 * Exporta a configuração do Vite para a aplicação.
 *
 * @since 1.0.0
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ENTRADAS_APLICACAO,
            refresh: true,
        }),
    ],

    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
            },
        },
    },
});

import GestorModalAvaliacao
    from '../modulos/GestorModalAvaliacao';

import InicializadorComentarios
    from '../modulos/InicializadorComentarios';

import InicializadorTooltips
    from '../modulos/InicializadorTooltips';

/**
 * Configura os comportamentos da página de detalhes de uma MetalThursday.
 *
 * @since 1.0.0
 */

/**
 * Inicia os componentes da página.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarPaginaDetalhesMetalThursday() {
    new InicializadorTooltips();
    new GestorModalAvaliacao();
    new InicializadorComentarios();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaDetalhesMetalThursday,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaDetalhesMetalThursday();
}

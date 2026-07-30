import inicializarFormularioMetalThursday
    from '../modulos/InicializadorFormularioMetalThursday';

/**
 * Inicializa a página de criação de uma MetalThursday.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function iniciarPaginaCriacaoMetalThursday() {
    inicializarFormularioMetalThursday(
        'formulario-criar-metal-thursday',
    );
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaCriacaoMetalThursday,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaCriacaoMetalThursday();
}

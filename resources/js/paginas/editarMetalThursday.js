import inicializarFormularioMetalThursday
    from '../modulos/InicializadorFormularioMetalThursday';

/**
 * Inicializa a página de edição de uma MetalThursday.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarPaginaEdicaoMetalThursday() {
    inicializarFormularioMetalThursday(
        'formulario-editar-metal-thursday',
    );
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaEdicaoMetalThursday,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaEdicaoMetalThursday();
}

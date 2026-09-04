import HistoricoArtistaMetalThursday
    from '../modulos/HistoricoArtistaMetalThursday';
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
    const identificadorFormulario =
        'formulario-editar-metal-thursday';

    inicializarFormularioMetalThursday(
        identificadorFormulario,
    );

    const formulario =
        document.getElementById(
            identificadorFormulario,
        );

    if (!(formulario instanceof HTMLFormElement)) {
        throw new TypeError(
            'Não foi encontrado o formulário de edição da MetalThursday.',
        );
    }

    new HistoricoArtistaMetalThursday(
        formulario,
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

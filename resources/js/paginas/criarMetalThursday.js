import HistoricoArtistaMetalThursday
    from '../modulos/HistoricoArtistaMetalThursday';
import inicializarFormularioMetalThursday
    from '../modulos/InicializadorFormularioMetalThursday';

/**
 * Inicializa a página de criação de uma MetalThursday.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarPaginaCriacaoMetalThursday() {
    const identificadorFormulario =
        'formulario-criar-metal-thursday';

    inicializarFormularioMetalThursday(
        identificadorFormulario,
    );

    const formulario =
        document.getElementById(
            identificadorFormulario,
        );

    if (!(formulario instanceof HTMLFormElement)) {
        throw new TypeError(
            'Não foi encontrado o formulário de criação da MetalThursday.',
        );
    }

    new HistoricoArtistaMetalThursday(
        formulario,
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

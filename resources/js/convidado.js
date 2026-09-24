/**
 * Ponto de entrada JavaScript das páginas destinadas a visitantes.
 *
 * Mantém o carregamento global destas páginas reduzido aos comportamentos
 * Bootstrap que estejam efetivamente presentes no documento.
 *
 * @since 2.0.0
 */

import { iniciarBootstrapConvidado }
    from './modulos/CarregadorBootstrapConvidado';

/**
 * Inicia os comportamentos globais das páginas de visitante.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function iniciarAplicacaoConvidado() {
    void iniciarBootstrapConvidado();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarAplicacaoConvidado,
        {
            once: true,
        },
    );
} else {
    iniciarAplicacaoConvidado();
}

/**
 * Ponto de entrada principal do JavaScript global da aplicação.
 *
 * Carrega o JavaScript completo do Bootstrap e inicializa os comportamentos
 * que devem estar disponíveis em todas as páginas.
 *
 * @since 1.0.0
 */

import './bootstrap';
import 'bootstrap';

import GestorInteracoes from './modulos/GestorInteracoes';
import LimpadorFormulariosModais from './modulos/LimpadorFormulariosModais';

/**
 * Seletores utilizados pelos comportamentos globais.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 */
const SELETORES = Object.freeze({
    ligacaoTerminarSessao: '[data-terminar-sessao]',
    formularioTerminarSessao: '#formulario-terminar-sessao',
});

/**
 * Inicia os módulos globais da aplicação.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarModulosGlobais() {
    new GestorInteracoes();
    new LimpadorFormulariosModais();
}

/**
 * Inicia o comportamento dos elementos que terminam a sessão.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarTerminoSessao() {
    const formulario = document.querySelector(
        SELETORES.formularioTerminarSessao,
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    document.addEventListener('click', (evento) => {
        const elementoClicado = evento.target;

        if (!(elementoClicado instanceof Element)) {
            return;
        }

        const acionador = elementoClicado.closest(
            SELETORES.ligacaoTerminarSessao,
        );

        if (!(acionador instanceof HTMLElement)) {
            return;
        }

        evento.preventDefault();

        formulario.requestSubmit();
    });
}

/**
 * Inicia os comportamentos globais da aplicação.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarAplicacao() {
    iniciarModulosGlobais();
    iniciarTerminoSessao();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarAplicacao,
        {
            once: true,
        },
    );
} else {
    iniciarAplicacao();
}

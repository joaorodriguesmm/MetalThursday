/**
 * Ponto de entrada principal do JavaScript global da aplicação.
 *
 * Inicializa as dependências e os comportamentos que devem estar
 * disponíveis em todas as páginas.
 *
 * @since 1.0.0
 * @version 2.0.0
 */

import './bootstrap';

import * as bootstrap
    from 'bootstrap';

import TomSelect
    from 'tom-select';

import Swal
    from 'sweetalert2';

import GestorInteracoes
    from './modulos/GestorInteracoes';

import LimpadorFormulariosModais
    from './modulos/LimpadorFormulariosModais';

/**
 * Seletores utilizados pelos comportamentos globais da aplicação.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
const SELETORES = Object.freeze({
    ligacaoTerminarSessao:
        '.logout-link',

    formularioTerminarSessao:
        '#logout-form',

    contentorVideo:
        '.video-lazy-load',
});

/**
 * Domínios permitidos para a incorporação de vídeos do YouTube.
 *
 * @type {ReadonlyArray<string>}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
const DOMINIOS_VIDEO_PERMITIDOS = Object.freeze([
    'www.youtube.com',
    'youtube.com',
    'www.youtube-nocookie.com',
    'youtube-nocookie.com',
]);

/**
 * Disponibiliza globalmente as dependências utilizadas pelos scripts
 * que ainda dependem de propriedades do objeto Window.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function disponibilizarDependenciasGlobais() {
    window.bootstrap = bootstrap;
    window.TomSelect = TomSelect;
    window.Swal = Swal;
}

/**
 * Inicia os módulos globais da aplicação.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarModulosGlobais() {
    new GestorInteracoes();
    new LimpadorFormulariosModais();
}

/**
 * Submete o formulário responsável por terminar a sessão.
 *
 * A submissão nativa evita que eventuais tratadores de submissões
 * assíncronas intercetem o pedido de término da sessão.
 *
 * @param {HTMLFormElement} formulario Formulário de término da sessão.
 *
 * @return {void}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function submeterFormularioTerminarSessao(
    formulario,
) {
    HTMLFormElement.prototype.submit.call(
        formulario,
    );
}

/**
 * Inicia o comportamento das ligações para terminar a sessão.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarTerminoSessao() {
    const formulario =
        document.querySelector(
            SELETORES.formularioTerminarSessao,
        );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    document.addEventListener(
        'click',
        (evento) => {
            const elementoClicado =
                evento.target;

            if (!(elementoClicado instanceof Element)) {
                return;
            }

            const ligacao =
                elementoClicado.closest(
                    SELETORES.ligacaoTerminarSessao,
                );

            if (!(ligacao instanceof HTMLElement)) {
                return;
            }

            evento.preventDefault();

            submeterFormularioTerminarSessao(
                formulario,
            );
        },
    );
}

/**
 * Obtém um URL permitido para incorporação de vídeo.
 *
 * Apenas são aceites URLs HTTPS de incorporação pertencentes aos
 * domínios autorizados do YouTube.
 *
 * @param {unknown} valor Valor do URL a validar.
 *
 * @return {string|null} URL normalizado ou nulo quando não é permitido.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function obterUrlVideoPermitido(valor) {
    if (
        typeof valor !== 'string'
        || valor.trim() === ''
    ) {
        return null;
    }

    try {
        const url = new URL(
            valor,
            window.location.origin,
        );

        const dominioPermitido =
            DOMINIOS_VIDEO_PERMITIDOS.includes(
                url.hostname,
            );

        const caminhoPermitido =
            url.pathname.startsWith('/embed/');

        if (
            url.protocol !== 'https:'
            || !dominioPermitido
            || !caminhoPermitido
        ) {
            return null;
        }

        return url.href;
    } catch {
        return null;
    }
}

/**
 * Cria o elemento de incorporação de um vídeo do YouTube.
 *
 * @param {string} url URL autorizado do vídeo.
 *
 * @return {HTMLDivElement} Contentor do vídeo criado.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function criarIncorporacaoVideo(url) {
    const contentorProporcao =
        document.createElement('div');

    contentorProporcao.classList.add(
        'ratio',
        'ratio-16x9',
    );

    const iframe =
        document.createElement('iframe');

    iframe.src = url;
    iframe.title = 'Vídeo do YouTube';
    iframe.loading = 'lazy';

    iframe.referrerPolicy =
        'strict-origin-when-cross-origin';

    iframe.allow = [
        'accelerometer',
        'autoplay',
        'clipboard-write',
        'encrypted-media',
        'gyroscope',
        'picture-in-picture',
        'web-share',
    ].join('; ');

    iframe.allowFullscreen = true;

    contentorProporcao.append(
        iframe,
    );

    return contentorProporcao;
}

/**
 * Carrega um vídeo dentro do respetivo contentor.
 *
 * @param {HTMLElement} contentorVideo Contentor do vídeo.
 *
 * @return {void}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function carregarVideo(contentorVideo) {
    const url =
        obterUrlVideoPermitido(
            contentorVideo.dataset.videoUrl,
        );

    if (url === null) {
        return;
    }

    contentorVideo.replaceChildren(
        criarIncorporacaoVideo(url),
    );
}

/**
 * Inicia o carregamento diferido dos vídeos do YouTube.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarCarregamentoVideos() {
    document.addEventListener(
        'click',
        (evento) => {
            const elementoClicado =
                evento.target;

            if (!(elementoClicado instanceof Element)) {
                return;
            }

            const contentorVideo =
                elementoClicado.closest(
                    SELETORES.contentorVideo,
                );

            if (!(contentorVideo instanceof HTMLElement)) {
                return;
            }

            carregarVideo(
                contentorVideo,
            );
        },
    );
}

/**
 * Inicia os comportamentos globais da aplicação.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarAplicacao() {
    iniciarModulosGlobais();
    iniciarTerminoSessao();
    iniciarCarregamentoVideos();
}

disponibilizarDependenciasGlobais();

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

import GestorModalAvaliacao from '../modulos/GestorModalAvaliacao';
import InicializadorTooltips from '../modulos/InicializadorTooltips';
import TratadorFormularioAjax from '../modulos/TratadorFormularioAjax';
import ValidadorFormulario from '../modulos/ValidadorFormulario';

let contadorFormulariosComentario = 0;

/**
 * Garante que um formulário possui um identificador HTML único.
 *
 * @param {HTMLFormElement} formulario Formulário recebido.
 *
 * @returns {string} Identificador do formulário.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function garantirIdentificadorFormulario(formulario) {
    if (formulario.id.trim() !== '') {
        return formulario.id;
    }

    let identificador;

    do {
        contadorFormulariosComentario += 1;

        identificador =
            `formulario-comentario-${contadorFormulariosComentario}`;
    } while (
        document.getElementById(
            identificador,
        ) !== null
    );

    formulario.id = identificador;

    return identificador;
}

/**
 * Converte a resposta da criação de um comentário num elemento HTML.
 *
 * São suportadas temporariamente respostas HTML diretas e objetos com as
 * propriedades `conteudo_html` ou `html`.
 *
 * @param {unknown} dadosResposta Resposta devolvida pelo servidor.
 *
 * @returns {HTMLElement|null} Comentário criado ou nulo.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function criarElementoComentario(dadosResposta) {
    const html = typeof dadosResposta === 'string'
        ? dadosResposta
        : dadosResposta?.conteudo_html
            ?? dadosResposta?.html
            ?? null;

    if (
        typeof html !== 'string'
        || html.trim() === ''
    ) {
        return null;
    }

    const modelo = document.createElement(
        'template',
    );

    modelo.innerHTML = html.trim();

    const comentario =
        modelo.content.firstElementChild;

    return comentario instanceof HTMLElement
        ? comentario
        : null;
}

/**
 * Insere um comentário na lista ou no conjunto de respostas correspondente.
 *
 * Os seletores permanecem temporariamente em inglês por corresponderem às
 * views atuais.
 *
 * @param {HTMLFormElement} formulario Formulário submetido.
 * @param {HTMLElement} comentario Comentário criado.
 *
 * @returns {boolean} Indica se o comentário foi inserido.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function inserirComentario(
    formulario,
    comentario,
) {
    const contentorFormularioResposta =
        formulario.closest(
            '.reply-form-container',
        );

    if (
        contentorFormularioResposta
        instanceof HTMLElement
    ) {
        const contentorComentario =
            formulario.closest(
                '.comment-container',
            );

        const contentorRespostas =
            contentorComentario?.querySelector(
                '.replies-container',
            );

        if (!(contentorRespostas instanceof HTMLElement)) {
            return false;
        }

        contentorRespostas.append(
            comentario,
        );

        contentorFormularioResposta.style.display =
            'none';

        contentorFormularioResposta.setAttribute(
            'aria-hidden',
            'true',
        );

        return true;
    }

    const areaComentarios = formulario.closest(
        '.card-body, .card-footer + .collapse',
    );

    const listaComentarios =
        areaComentarios?.querySelector(
            '.comments-list',
        );

    if (!(listaComentarios instanceof HTMLElement)) {
        return false;
    }

    listaComentarios.querySelector(
        '.no-comments-placeholder',
    )?.remove();

    listaComentarios.append(
        comentario,
    );

    return true;
}

/**
 * Inicializa os formulários de comentário existentes num contentor.
 *
 * Os seletores e o nome do campo permanecem temporariamente em inglês por
 * corresponderem às views atuais.
 *
 * @param {Document|Element} contentor Contentor de pesquisa.
 * @param {InicializadorTooltips} inicializadorTooltips
 *     Inicializador dos tooltips.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function inicializarFormulariosComentario(
    contentor,
    inicializadorTooltips,
) {
    contentor.querySelectorAll(
        '.comment-form',
    ).forEach((formulario) => {
        if (
            !(formulario instanceof HTMLFormElement)
            || formulario.dataset
                .formularioComentarioInicializado
                === 'true'
        ) {
            return;
        }

        formulario.dataset
            .formularioComentarioInicializado =
                'true';

        const identificadorFormulario =
            garantirIdentificadorFormulario(
                formulario,
            );

        const tratadorAjax =
            new TratadorFormularioAjax(
                identificadorFormulario,
                formulario.action,
                (dadosResposta) => {
                    const comentario =
                        criarElementoComentario(
                            dadosResposta,
                        );

                    if (
                        !(comentario instanceof HTMLElement)
                        || !inserirComentario(
                            formulario,
                            comentario,
                        )
                    ) {
                        return;
                    }

                    inicializarFormulariosComentario(
                        comentario,
                        inicializadorTooltips,
                    );

                    inicializadorTooltips.atualizar();
                },
            );

        new ValidadorFormulario(
            formulario,
            {
                regras: {
                    content: [
                        'obrigatorio',
                    ],
                },

                mensagens: {
                    content: {
                        obrigatorio:
                            'Por favor, insere o texto do comentário.',
                    },
                },

                aoSucesso: () => {
                    /*
                     * A promessa não é devolvida porque o validador exige
                     * uma função de sucesso síncrona.
                     */
                    tratadorAjax.submeter();
                },
            },
        );
    });
}

/**
 * Inicializa os componentes da página de detalhes de uma MetalThursday.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function inicializarPaginaDetalhesMetalThursday() {
    const inicializadorTooltips =
        new InicializadorTooltips();

    new GestorModalAvaliacao();

    inicializarFormulariosComentario(
        document,
        inicializadorTooltips,
    );
}

document.addEventListener(
    'DOMContentLoaded',
    inicializarPaginaDetalhesMetalThursday,
    {
        once: true,
    },
);

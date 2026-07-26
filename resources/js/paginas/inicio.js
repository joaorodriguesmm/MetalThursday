import AlternadorVistas from '../modulos/AlternadorVistas';
import GestorFiltrosDinamicos from '../modulos/GestorFiltrosDinamicos';
import GestorModalAvaliacao from '../modulos/GestorModalAvaliacao';
import InicializadorTooltips from '../modulos/InicializadorTooltips';
import TratadorFormularioAjax from '../modulos/TratadorFormularioAjax';
import ValidadorFormulario from '../modulos/ValidadorFormulario';

let contadorFormulariosComentario = 0;

/**
 * Normaliza os dados utilizados pelos filtros dinâmicos.
 *
 * Mantém os contratos atuais disponibilizados pela view e acrescenta o nome
 * português esperado pelo novo gestor quando os registos ainda usam `name`.
 *
 * @param {unknown} dados Dados recebidos da página.
 *
 * @returns {Record<string, unknown>} Dados normalizados.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function normalizarDadosFiltros(dados) {
    if (
        dados === null
        || typeof dados !== 'object'
        || Array.isArray(dados)
    ) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(dados).map(
            ([chave, valores]) => {
                if (!Array.isArray(valores)) {
                    return [
                        chave,
                        valores,
                    ];
                }

                return [
                    chave,
                    valores.map((valor) => {
                        if (
                            valor === null
                            || typeof valor !== 'object'
                            || Array.isArray(valor)
                        ) {
                            return valor;
                        }

                        return {
                            ...valor,

                            nome:
                                valor.nome
                                ?? valor.name
                                ?? valor.text
                                ?? '',
                        };
                    }),
                ];
            },
        ),
    );
}

/**
 * Converte um tipo antigo de filtro para a designação portuguesa.
 *
 * @param {unknown} tipo Tipo recebido.
 *
 * @returns {string} Tipo normalizado.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function normalizarTipoFiltro(tipo) {
    const tipos = {
        boolean: 'sim_nao',
        date: 'data',
        select: 'selecao',
        selection: 'selecao',
        yes_no: 'sim_nao',
    };

    if (typeof tipo !== 'string') {
        return 'selecao';
    }

    const tipoNormalizado =
        tipo.trim();

    return tipos[tipoNormalizado]
        ?? tipoNormalizado;
}

/**
 * Normaliza a configuração dos filtros disponibilizada pela view.
 *
 * @param {unknown} filtros Configuração recebida.
 *
 * @returns {Record<string, object>} Configuração normalizada.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function normalizarFiltrosDisponiveis(filtros) {
    if (
        filtros === null
        || typeof filtros !== 'object'
        || Array.isArray(filtros)
    ) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(filtros).map(
            ([chave, configuracao]) => {
                const configuracaoValida =
                    configuracao !== null
                    && typeof configuracao === 'object'
                    && !Array.isArray(configuracao)
                        ? configuracao
                        : {};

                return [
                    chave,
                    {
                        ...configuracaoValida,

                        parametro:
                            configuracaoValida.parametro
                            ?? configuracaoValida.param
                            ?? configuracaoValida.parameter
                            ?? chave,

                        tipo: normalizarTipoFiltro(
                            configuracaoValida.tipo
                            ?? configuracaoValida.type
                            ?? 'selecao',
                        ),

                        rotulo:
                            configuracaoValida.rotulo
                            ?? configuracaoValida.label
                            ?? chave,

                        chaveDados:
                            configuracaoValida.chaveDados
                            ?? configuracaoValida.dataKey
                            ?? configuracaoValida.data_key
                            ?? null,
                    },
                ];
            },
        ),
    );
}

/**
 * Obtém os valores das vistas definidos pela página.
 *
 * @returns {{completa: string, simplificada: string}}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function obterVistas() {
    const traducoes =
        window.viewTranslations
        ?? {};

    return {
        completa:
            traducoes.completa
            ?? traducoes.full
            ?? 'full',

        simplificada:
            traducoes.simplificada
            ?? traducoes.simplified
            ?? 'simplified',
    };
}

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
function garantirIdentificadorFormulario(
    formulario,
) {
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

    formulario.id =
        identificador;

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
function criarElementoComentario(
    dadosResposta,
) {
    const html =
        typeof dadosResposta === 'string'
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

    modelo.innerHTML =
        html.trim();

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

        if (
            !(
                contentorRespostas
                instanceof HTMLElement
            )
        ) {
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

    const areaComentarios =
        formulario.closest('.card-body')
        ?? formulario.closest('.collapse');

    const listaComentarios =
        areaComentarios?.querySelector(
            '.comments-list',
        );

    if (
        !(
            listaComentarios
            instanceof HTMLElement
        )
    ) {
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
 * Os seletores e os nomes dos campos permanecem temporariamente em inglês
 * por corresponderem às views ainda não analisadas.
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
                     * A promessa não deve ser devolvida porque o
                     * ValidadorFormulario exige um callback síncrono.
                     */
                    tratadorAjax.submeter();
                },
            },
        );
    });
}

/**
 * Configura a submissão automática dos campos de filtro e ordenação.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function configurarSubmissaoAutomatica() {
    const formulario = document.getElementById(
        'filter-sort-form',
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    formulario.querySelectorAll(
        '.auto-submit',
    ).forEach((campo) => {
        if (
            !(
                campo instanceof HTMLInputElement
                || campo instanceof HTMLSelectElement
            )
            || campo.dataset
                .submissaoAutomaticaInicializada
                === 'true'
        ) {
            return;
        }

        campo.dataset
            .submissaoAutomaticaInicializada =
                'true';

        campo.addEventListener(
            'change',
            () => {
                if (
                    typeof formulario.requestSubmit
                    === 'function'
                ) {
                    formulario.requestSubmit();

                    return;
                }

                formulario.submit();
            },
        );
    });
}

/**
 * Inicializa os componentes da página inicial.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function inicializarPaginaInicio() {
    const inicializadorTooltips =
        new InicializadorTooltips();

    new GestorModalAvaliacao();

    if (
        document.querySelector(
            '#add_filter_dropdown',
        )
        && document.querySelector(
            '#active-filters-area',
        )
    ) {
        new GestorFiltrosDinamicos({
            seletorListaFiltros:
                '#add_filter_dropdown',

            seletorContentorFiltros:
                '#active-filters-area',

            dadosFiltros:
                normalizarDadosFiltros(
                    window.filterData
                    ?? {},
                ),

            filtrosDisponiveis:
                normalizarFiltrosDisponiveis(
                    window.availableFilters
                    ?? {},
                ),
        });
    }

    configurarSubmissaoAutomatica();

    if (
        document.querySelector(
            '#view-toggle-btn',
        )
        && document.querySelector(
            '#view-type-input',
        )
        && document.querySelector(
            '#filter-sort-form',
        )
    ) {
        new AlternadorVistas({
            seletorBotao:
                '#view-toggle-btn',

            seletorCampoVista:
                '#view-type-input',

            seletorFormulario:
                '#filter-sort-form',

            vistas:
                obterVistas(),
        });
    }

    inicializarFormulariosComentario(
        document,
        inicializadorTooltips,
    );
}

document.addEventListener(
    'DOMContentLoaded',
    inicializarPaginaInicio,
    {
        once: true,
    },
);

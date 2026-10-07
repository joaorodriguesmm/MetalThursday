import AlternadorVistas
    from '../modulos/AlternadorVistas';

import GestorFiltrosDinamicos
    from '../modulos/GestorFiltrosDinamicos';

/**
 * Inicializa os comportamentos da página principal de MetalThursdays.
 *
 * Gere os filtros, a ordenação, a alternância de vistas e, quando disponíveis
 * na página, as avaliações e a publicação de comentários.
 *
 * @since 1.0.0
 */

/**
 * Seletores utilizados na página.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 */
const SELETORES = Object.freeze({
    configuracaoListagem:
        '#configuracao-listagem-metal-thursday',

    formularioFiltros:
        '#formulario-filtros-ordenacao',

    submissaoAutomatica:
        '.submissao-automatica',

    seletorAdicionarFiltro:
        '#seletor-adicionar-filtro',

    contentorFiltros:
        '#area-filtros-ativos',

    botaoAlternarVista:
        '#botao-alternar-vista',

    campoTipoVista:
        '#campo-tipo-vista',

    acionadorAvaliacao:
        '[data-endereco-avaliacao][data-bs-target="#modal-avaliacao"]',

    interacaoComentarios: [
        '.formulario-comentario',
        'button[data-acao-comentarios="alternar-seccao"][data-endereco-comentarios]',
    ].join(', '),

    tooltip:
        '[data-bs-toggle="tooltip"]',
});

/**
 * Determina se um valor é um objeto não nulo.
 *
 * @param {unknown} valor Valor recebido.
 *
 * @returns {boolean} Verdadeiro quando o valor é um objeto.
 *
 * @since 2.0.0
 */
function eObjeto(valor) {
    return typeof valor === 'object'
        && valor !== null
        && !Array.isArray(valor);
}

/**
 * Obtém a configuração preparada pelo servidor para a listagem.
 *
 * @returns {{
 *     dadosFiltros: Record<string, Array<object>>,
 *     filtrosDisponiveis: Record<string, object>,
 *     vistas: {
 *         completa: string,
 *         simplificada: string
 *     }
 * }} Configuração validada.
 *
 * @throws {Error} Quando o bloco de configuração não existe ou contém JSON
 *     inválido.
 * @throws {TypeError} Quando a configuração não respeita o contrato esperado.
 *
 * @since 2.0.0
 */
function obterConfiguracaoListagem() {
    const elemento =
        document.querySelector(
            SELETORES.configuracaoListagem,
        );

    if (!(elemento instanceof HTMLScriptElement)) {
        throw new Error(
            'Não foi encontrada a configuração da listagem de MetalThursdays.',
        );
    }

    const conteudo =
        elemento.textContent?.trim()
        ?? '';

    if (conteudo === '') {
        throw new Error(
            'A configuração da listagem de MetalThursdays está vazia.',
        );
    }

    let configuracao;

    try {
        configuracao =
            JSON.parse(
                conteudo,
            );
    } catch (erro) {
        throw new Error(
            'A configuração da listagem de MetalThursdays contém JSON inválido.',
            {
                cause: erro,
            },
        );
    }

    if (!eObjeto(configuracao)) {
        throw new TypeError(
            'A configuração da listagem de MetalThursdays deve ser um objeto.',
        );
    }

    [
        'dadosFiltros',
        'filtrosDisponiveis',
        'vistas',
    ].forEach((chave) => {
        if (!eObjeto(configuracao[chave])) {
            throw new TypeError(
                `A propriedade "${chave}" da configuração da listagem é inválida.`,
            );
        }
    });

    return configuracao;
}

/**
 * Configura a submissão automática dos campos de paginação e ordenação.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function configurarSubmissaoAutomatica() {
    const formulario =
        document.querySelector(
            SELETORES.formularioFiltros,
        );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    formulario.querySelectorAll(
        SELETORES.submissaoAutomatica,
    ).forEach((campo) => {
        if (
            !(
                campo instanceof HTMLInputElement
                || campo instanceof HTMLSelectElement
            )
        ) {
            return;
        }

        campo.addEventListener(
            'change',
            () => {
                formulario.requestSubmit();
            },
        );
    });
}

/**
 * Prepara o carregamento dos tooltips apenas na primeira interação relevante.
 *
 * O Bootstrap Tooltip e as respetivas dependências só são transferidos quando
 * o utilizador tenta consultar um tooltip. A primeira interação é reproduzida
 * depois da inicialização delegada para não exigir uma segunda ação.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function prepararTooltipsSobPedido() {
    const tiposEvento = [
        'mouseover',
        'focusin',
    ];

    let carregamentoEmCurso =
        false;

    const registarEventos = () => {
        tiposEvento.forEach(
            (tipoEvento) => {
                document.addEventListener(
                    tipoEvento,
                    tratarPrimeiraInteracao,
                    true,
                );
            },
        );
    };

    const removerEventos = () => {
        tiposEvento.forEach(
            (tipoEvento) => {
                document.removeEventListener(
                    tipoEvento,
                    tratarPrimeiraInteracao,
                    true,
                );
            },
        );
    };

    const tratarPrimeiraInteracao = (
        evento,
    ) => {
        if (
            carregamentoEmCurso
            || !(evento.target instanceof Element)
        ) {
            return;
        }

        const alvo =
            evento.target.closest(
                SELETORES.tooltip,
            );

        if (!(alvo instanceof HTMLElement)) {
            return;
        }

        carregamentoEmCurso =
            true;

        removerEventos();

        void import(
            '../modulos/InicializadorTooltips'
        )
            .then(
                ({
                    default:
                        InicializadorTooltips,
                }) => {
                    new InicializadorTooltips(
                        document.body,
                        {
                            selector:
                                SELETORES.tooltip,
                        },
                    );

                    if (!alvo.isConnected) {
                        return;
                    }

                    if (evento.type === 'mouseover') {
                        if (!alvo.matches(':hover')) {
                            return;
                        }

                        alvo.dispatchEvent(
                            new MouseEvent(
                                'mouseover',
                                {
                                    bubbles:
                                        true,

                                    relatedTarget:
                                        null,
                                },
                            ),
                        );

                        return;
                    }

                    if (
                        document.activeElement !== alvo
                        && !alvo.contains(
                            document.activeElement,
                        )
                    ) {
                        return;
                    }

                    alvo.dispatchEvent(
                        new FocusEvent(
                            'focusin',
                            {
                                bubbles:
                                    true,
                            },
                        ),
                    );
                },
            )
            .catch(
                () => {
                    carregamentoEmCurso =
                        false;

                    registarEventos();
                },
            );
    };

    registarEventos();
}

/**
 * Inicializa os componentes de interação apenas quando são utilizados pela
 * vista apresentada.
 *
 * A vista simplificada não contém controlos de avaliação nem interações de
 * comentários, pelo que evita transferir estes módulos sem necessidade.
 *
 * @returns {Promise<void>}
 *
 * @since 2.0.0
 */
async function iniciarInteracoesDisponiveis() {
    const tarefas =
        [];

    if (
        document.querySelector(
            SELETORES.acionadorAvaliacao,
        ) !== null
    ) {
        tarefas.push(
            import(
                '../modulos/GestorModalAvaliacao'
            ).then(
                ({
                    default:
                        GestorModalAvaliacao,
                }) => {
                    new GestorModalAvaliacao();
                },
            ),
        );
    }

    if (
        document.querySelector(
            SELETORES.interacaoComentarios,
        ) !== null
    ) {
        tarefas.push(
            import(
                '../modulos/InicializadorComentarios'
            ).then(
                ({
                    default:
                        InicializadorComentarios,
                }) => {
                    new InicializadorComentarios();
                },
            ),
        );
    }

    await Promise.all(
        tarefas,
    );
}

/**
 * Inicializa os componentes da página principal.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarPaginaInicio() {
    const configuracao =
        obterConfiguracaoListagem();

    prepararTooltipsSobPedido();

    new GestorFiltrosDinamicos({
        seletorListaFiltros:
            SELETORES.seletorAdicionarFiltro,

        seletorContentorFiltros:
            SELETORES.contentorFiltros,

        dadosFiltros:
            configuracao.dadosFiltros,

        filtrosDisponiveis:
            configuracao.filtrosDisponiveis,
    });

    configurarSubmissaoAutomatica();

    new AlternadorVistas({
        seletorBotao:
            SELETORES.botaoAlternarVista,

        seletorCampoVista:
            SELETORES.campoTipoVista,

        seletorFormulario:
            SELETORES.formularioFiltros,

        vistas:
            configuracao.vistas,
    });

    void iniciarInteracoesDisponiveis();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaInicio,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaInicio();
}

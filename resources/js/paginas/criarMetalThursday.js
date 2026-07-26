import GestorFormulariosModais from '../modulos/GestorFormulariosModais';
import GestorSeccoes from '../modulos/GestorSeccoes';
import InicializadorTomSelect from '../modulos/InicializadorTomSelect';
import InicializadorTooltips from '../modulos/InicializadorTooltips';
import SeletorNomeados from '../modulos/SeletorNomeados';
import TestadorIncorporacao from '../modulos/TestadorIncorporacao';
import ValidadorFormulario from '../modulos/ValidadorFormulario';

/**
 * Inicializa os componentes pertencentes a uma secção.
 *
 * Os seletores CSS permanecem temporariamente em inglês por corresponderem
 * à estrutura atual das views.
 *
 * @param {HTMLElement} seccao
 *     Secção que deve ser inicializada.
 * @param {InicializadorTomSelect} inicializadorTomSelect
 *     Inicializador dos campos Tom Select.
 * @param {InicializadorTooltips} inicializadorTooltips
 *     Inicializador dos tooltips do Bootstrap.
 *
 * @returns {void}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function inicializarComponentesSeccao(
    seccao,
    inicializadorTomSelect,
    inicializadorTooltips,
) {
    if (!(seccao instanceof HTMLElement)) {
        return;
    }

    inicializadorTomSelect.iniciarTodos(
        seccao,
    );

    new TestadorIncorporacao(
        seccao,
    );

    inicializadorTooltips.atualizar();
}

/**
 * Apresenta ou remove o erro geral das secções.
 *
 * @param {HTMLElement|null} elemento
 *     Elemento de feedback.
 * @param {string} mensagem
 *     Mensagem a apresentar.
 *
 * @returns {void}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function atualizarErroSeccoes(
    elemento,
    mensagem,
) {
    if (!(elemento instanceof HTMLElement)) {
        return;
    }

    const possuiErro =
        mensagem !== '';

    elemento.textContent =
        mensagem;

    elemento.classList.toggle(
        'd-block',
        possuiErro,
    );

    if (possuiErro) {
        elemento.removeAttribute(
            'hidden',
        );

        return;
    }

    elemento.setAttribute(
        'hidden',
        '',
    );
}

/**
 * Valida um campo pertencente a uma secção.
 *
 * @param {ValidadorFormulario} validador
 *     Validador do formulário principal.
 * @param {Element|null} campo
 *     Campo que deve ser validado.
 * @param {Array<string|Function>} regras
 *     Regras aplicáveis.
 * @param {Object<string, string>} mensagens
 *     Mensagens das regras.
 *
 * @returns {boolean}
 *     Verdadeiro quando o campo existe e é válido.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function validarCampoSeccao(
    validador,
    campo,
    regras,
    mensagens,
) {
    if (
        !(campo instanceof HTMLInputElement)
        && !(campo instanceof HTMLSelectElement)
        && !(campo instanceof HTMLTextAreaElement)
    ) {
        return false;
    }

    return validador.validarCampoComRegras(
        campo,
        regras,
        mensagens,
    );
}

/**
 * Valida as secções do formulário principal.
 *
 * @param {ValidadorFormulario} validador
 *     Validador do formulário.
 *
 * @returns {boolean}
 *     Verdadeiro quando todas as secções são válidas.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function validarSeccoes(
    validador,
) {
    const contentorSeccoes =
        document.getElementById(
            'sections-container',
        );

    const elementoFeedback =
        document.getElementById(
            'sections-validation-feedback',
        );

    if (!(contentorSeccoes instanceof HTMLElement)) {
        atualizarErroSeccoes(
            elementoFeedback,
            'Não foi possível validar as secções.',
        );

        return false;
    }

    const seccoes = Array.from(
        contentorSeccoes.querySelectorAll(
            '.section-item',
        ),
    ).filter(
        (seccao) =>
            seccao instanceof HTMLElement,
    );

    if (seccoes.length === 0) {
        atualizarErroSeccoes(
            elementoFeedback,
            'É necessário adicionar, pelo menos, uma secção.',
        );

        return false;
    }

    atualizarErroSeccoes(
        elementoFeedback,
        '',
    );

    let todasSeccoesValidas = true;

    seccoes.forEach((seccao) => {
        const selecaoTipo =
            seccao.querySelector(
                '.section-type-select',
            );

        const descricao =
            seccao.querySelector(
                'textarea[name*="[description]"]',
            );

        if (
            !validarCampoSeccao(
                validador,
                selecaoTipo,
                [
                    'obrigatorio',
                ],
                {
                    obrigatorio:
                        'Por favor, seleciona o tipo de secção.',
                },
            )
        ) {
            todasSeccoesValidas = false;
        }

        if (
            !validarCampoSeccao(
                validador,
                descricao,
                [
                    'obrigatorio',
                ],
                {
                    obrigatorio:
                        'Por favor, insere a descrição.',
                },
            )
        ) {
            todasSeccoesValidas = false;
        }

        if (
            !(
                selecaoTipo
                instanceof HTMLSelectElement
            )
        ) {
            todasSeccoesValidas = false;

            return;
        }

        const opcaoSelecionada =
            selecaoTipo.options[
                selecaoTipo.selectedIndex
            ]
            ?? null;

        const possuiDetalhes = [
            'true',
            '1',
        ].includes(
            opcaoSelecionada
                ?.dataset
                .hasDetails
            ?? '',
        );

        if (!possuiDetalhes) {
            return;
        }

        const camposDetalhes = [
            {
                campo: seccao.querySelector(
                    'select[name*="[band_id]"]',
                ),

                mensagem:
                    'Por favor, seleciona a banda.',
            },
            {
                campo: seccao.querySelector(
                    'input[name*="[title]"]',
                ),

                mensagem:
                    'Por favor, insere o título.',
            },
            {
                campo: seccao.querySelector(
                    'input[name*="[link]"]',
                ),

                mensagem:
                    'Por favor, insere a ligação.',
            },
            {
                campo: seccao.querySelector(
                    'input[name*="[year]"]',
                ),

                mensagem:
                    'Por favor, insere o ano.',
            },
        ];

        camposDetalhes.forEach(
            ({
                campo,
                mensagem,
            }) => {
                if (
                    !validarCampoSeccao(
                        validador,
                        campo,
                        [
                            'obrigatorio',
                        ],
                        {
                            obrigatorio:
                                mensagem,
                        },
                    )
                ) {
                    todasSeccoesValidas = false;
                }
            },
        );
    });

    return todasSeccoesValidas;
}

/**
 * Adiciona uma opção a uma instância Tom Select.
 *
 * @param {TomSelect|null} instancia
 *     Instância que deve receber a opção.
 * @param {unknown} valor
 *     Valor da opção.
 * @param {unknown} texto
 *     Texto apresentado.
 * @param {boolean} selecionar
 *     Indica se a opção deve ser selecionada.
 *
 * @returns {void}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function adicionarOpcaoTomSelect(
    instancia,
    valor,
    texto,
    selecionar = false,
) {
    if (
        !instancia
        || typeof instancia.addOption
            !== 'function'
        || valor === null
        || valor === undefined
        || typeof texto !== 'string'
        || texto.trim() === ''
    ) {
        return;
    }

    instancia.addOption({
        value: valor,
        text: texto,
    });

    if (
        selecionar
        && typeof instancia.setValue
            === 'function'
    ) {
        instancia.setValue(
            valor,
        );
    }
}

/**
 * Cria a configuração dos formulários apresentados em modais.
 *
 * Os nomes das propriedades recebidas do servidor permanecem temporariamente
 * em inglês para conservar os contratos atuais dos controladores.
 *
 * @param {InicializadorTomSelect} inicializadorTomSelect
 *     Inicializador dos campos Tom Select.
 *
 * @returns {Array<object>}
 *     Configurações dos modais.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function criarConfiguracoesModais(
    inicializadorTomSelect,
) {
    return [
        {
            idFormulario:
                'create-edition-form',

            url:
                window.editionStoreUrl,

            regrasValidacao: {
                name: [
                    'obrigatorio',
                    'maximo:255',
                ],

                start_date: [
                    'obrigatorio',
                    'data',
                ],

                end_date: [
                    'data',
                    'posterior_ou_igual:start_date',
                ],
            },

            mensagensValidacao: {
                name: {
                    obrigatorio:
                        'Por favor, insere o nome.',

                    maximo:
                        'O nome não pode ter mais de 255 caracteres.',
                },

                start_date: {
                    obrigatorio:
                        'Por favor, seleciona a data de início.',

                    data:
                        'A data de início deve ser válida.',
                },

                end_date: {
                    data:
                        'A data de fim deve ser válida.',

                    posterior_ou_igual:
                        'A data de fim não pode ser anterior à data de início.',
                },
            },

            aoSucesso: (
                dadosResposta,
            ) => {
                adicionarOpcaoTomSelect(
                    inicializadorTomSelect
                        .obterInstancia(
                            'edition_id',
                        ),

                    dadosResposta.id,
                    dadosResposta.display_text,
                    true,
                );
            },
        },
        {
            idFormulario:
                'create-band-form',

            url:
                window.bandStoreUrl,

            regrasValidacao: {
                name: [
                    'obrigatorio',
                    'maximo:255',
                ],

                country_id: [
                    'obrigatorio',
                ],

                'genres[]': [
                    'obrigatorio',
                ],
            },

            mensagensValidacao: {
                name: {
                    obrigatorio:
                        'Por favor, insere o nome.',

                    maximo:
                        'O nome não pode ter mais de 255 caracteres.',
                },

                country_id: {
                    obrigatorio:
                        'Por favor, seleciona o país.',
                },

                'genres[]': {
                    obrigatorio:
                        'Por favor, seleciona, pelo menos, um género.',
                },
            },

            aoSucesso: (
                dadosResposta,
            ) => {
                document
                    .querySelectorAll(
                        '.tom-select-bands',
                    )
                    .forEach(
                        (selecao) => {
                            adicionarOpcaoTomSelect(
                                selecao.tomselect
                                ?? null,

                                dadosResposta.id,
                                dadosResposta.name,
                            );
                        },
                    );
            },
        },
        {
            idFormulario:
                'create-genre-form',

            url:
                window.genreStoreUrl,

            regrasValidacao: {
                name: [
                    'obrigatorio',
                    'maximo:255',
                ],
            },

            mensagensValidacao: {
                name: {
                    obrigatorio:
                        'Por favor, insere o nome.',

                    maximo:
                        'O nome não pode ter mais de 255 caracteres.',
                },
            },

            aoSucesso: (
                dadosResposta,
            ) => {
                document
                    .querySelectorAll(
                        [
                            '.tom-select-multiple',
                            '#new_genre_parent_ids',
                        ].join(', '),
                    )
                    .forEach(
                        (selecao) => {
                            adicionarOpcaoTomSelect(
                                selecao.tomselect
                                ?? null,

                                dadosResposta.id,
                                dadosResposta.name,
                            );
                        },
                    );
            },
        },
    ];
}

/**
 * Inicializa a página de criação de uma MetalThursday.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function inicializarPaginaCriacaoMetalThursday() {
    const inicializadorTomSelect =
        new InicializadorTomSelect();

    const inicializadorTooltips =
        new InicializadorTooltips();

    const contentorSeccoes =
        document.getElementById(
            'sections-container',
        );

    if (contentorSeccoes instanceof HTMLElement) {
        contentorSeccoes
            .querySelectorAll(
                '.section-item',
            )
            .forEach((seccao) => {
                if (
                    seccao
                    instanceof HTMLElement
                ) {
                    new TestadorIncorporacao(
                        seccao,
                    );
                }
            });
    }

    new GestorSeccoes(
        '#sections-container',
        '#add-section-btn',
        '#section-template',
        (novaSeccao) => {
            inicializarComponentesSeccao(
                novaSeccao,
                inicializadorTomSelect,
                inicializadorTooltips,
            );
        },
    );

    new SeletorNomeados({
        seletorBotaoAleatorio:
            '#select-random-nominee',

        seletorBotaoMaisAntigo:
            '#select-oldest-nominee',

        instanciaTomSelect:
            inicializadorTomSelect
                .obterInstancia(
                    'next_nominee_id',
                ),

        urlNomeadoMaisAntigo:
            window.longestNotNominatedUrl,
    });

    new ValidadorFormulario(
        '#create-metalthursday-form',
        {
            regras: {
                edition_id: [
                    'obrigatorio',
                ],

                date: [
                    'obrigatorio',
                    'data',
                ],

                name: [
                    'maximo:255',
                ],

                author_id: [
                    'obrigatorio',
                ],

                next_nominee_id: [
                    'obrigatorio',
                ],
            },

            mensagens: {
                edition_id: {
                    obrigatorio:
                        'Por favor, seleciona a edição.',
                },

                date: {
                    obrigatorio:
                        'Por favor, seleciona a data.',

                    data:
                        'A data deve ser válida.',
                },

                name: {
                    maximo:
                        'O nome não pode ter mais de 255 caracteres.',
                },

                author_id: {
                    obrigatorio:
                        'Por favor, seleciona o autor.',
                },

                next_nominee_id: {
                    obrigatorio:
                        'Por favor, seleciona o nomeado.',
                },
            },

            validadorPersonalizado: (
                validador,
            ) => validarSeccoes(
                validador,
            ),
        },
    );

    new GestorFormulariosModais(
        criarConfiguracoesModais(
            inicializadorTomSelect,
        ),
        inicializadorTomSelect,
    );
}

document.addEventListener(
    'DOMContentLoaded',
    inicializarPaginaCriacaoMetalThursday,
    {
        once: true,
    },
);

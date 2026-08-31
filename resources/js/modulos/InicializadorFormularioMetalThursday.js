import { Modal } from 'bootstrap';
import {
    adicionarOpcaoTomSelect,
    obterOpcaoResposta,
} from './OpcoesTomSelect';
import GestorEdicaoMetalThursday from './GestorEdicaoMetalThursday';
import GestorFormulariosModais from './GestorFormulariosModais';
import GestorSeccoes from './GestorSeccoes';
import InicializadorTomSelect from './InicializadorTomSelect';
import InicializadorTooltips from './InicializadorTooltips';
import SeletorNomeados from './SeletorNomeados';
import TestadorIncorporacao from './TestadorIncorporacao';
import ValidadorFormulario from './ValidadorFormulario';

/**
 * Inicializa os comportamentos partilhados pelos formulários de criação e
 * edição de MetalThursdays.
 *
 * @since 2.0.0
 */

/**
 * Endereços obrigatórios da configuração preparada pelo servidor.
 *
 * @type {ReadonlyArray<string>}
 *
 * @since 2.0.0
 */
const CHAVES_ENDERECOS = Object.freeze([
    'guardarEdicao',
    'guardarArtista',
    'guardarGenero',
    'obterUtilizadorHaMaisTempoSemNomeacao',
]);

/**
 * Tipos de incorporação permitidos nas secções.
 *
 * @type {ReadonlyArray<string>}
 *
 * @since 2.0.0
 */
const TIPOS_INCORPORACAO = Object.freeze([
    'ligacao',
    'video_youtube',
    'lista_reproducao_youtube',
]);

/**
 * Determina se um valor é um objeto não nulo.
 *
 * @param {unknown} valor Valor recebido.
 *
 * @returns {boolean} Verdadeiro quando o valor é um objeto simples.
 *
 * @since 2.0.0
 */
function eObjeto(valor) {
    return typeof valor === 'object'
        && valor !== null
        && !Array.isArray(valor);
}

/**
 * Normaliza um endereço da configuração do formulário.
 *
 * Apenas são aceites endereços HTTP ou HTTPS da origem atual.
 *
 * @param {unknown} endereco Endereço recebido.
 * @param {string} chave Chave utilizada na mensagem de erro.
 *
 * @returns {string} Endereço normalizado.
 *
 * @throws {TypeError} Quando o endereço é inválido.
 *
 * @since 2.0.0
 */
function normalizarEndereco(endereco, chave) {
    if (
        typeof endereco !== 'string'
        || endereco.trim() === ''
    ) {
        throw new TypeError(
            `O endereço "${chave}" do formulário de MetalThursday é inválido.`,
        );
    }

    try {
        const url = new URL(
            endereco.trim(),
            window.location.origin,
        );

        if (
            !['http:', 'https:'].includes(url.protocol)
            || url.origin !== window.location.origin
        ) {
            throw new TypeError();
        }

        return url.href;
    } catch {
        throw new TypeError(
            `O endereço "${chave}" do formulário de MetalThursday é inválido.`,
        );
    }
}

/**
 * Obtém e valida a configuração global do formulário.
 *
 * @returns {{
 *     enderecos: Readonly<Record<string, string>>,
 *     fornecedoresIncorporacao: Array<object>
 * }} Configuração validada.
 *
 * @throws {TypeError} Quando a configuração é inválida.
 *
 * @since 2.0.0
 */
function obterConfiguracaoFormulario() {
    const configuracao =
        window.configuracaoFormularioMetalThursday;

    if (
        !eObjeto(configuracao)
        || !eObjeto(configuracao.enderecos)
        || !Array.isArray(
            configuracao.fornecedoresIncorporacao,
        )
    ) {
        throw new TypeError(
            'A configuração do formulário de MetalThursday é inválida.',
        );
    }

    const enderecos = Object.fromEntries(
        CHAVES_ENDERECOS.map((chave) => [
            chave,
            normalizarEndereco(
                configuracao.enderecos[chave],
                chave,
            ),
        ]),
    );

    return {
        enderecos: Object.freeze(enderecos),

        fornecedoresIncorporacao: [
            ...configuracao.fornecedoresIncorporacao,
        ],
    };
}

/**
 * Obtém um formulário obrigatório através do identificador HTML.
 *
 * @param {string} identificador Identificador do formulário.
 *
 * @returns {HTMLFormElement} Formulário encontrado.
 *
 * @throws {TypeError} Quando o identificador ou o formulário são inválidos.
 *
 * @since 2.0.0
 */
function obterFormulario(identificador) {
    if (
        typeof identificador !== 'string'
        || identificador.trim() === ''
    ) {
        throw new TypeError(
            'O identificador do formulário de MetalThursday é obrigatório.',
        );
    }

    const identificadorNormalizado =
        identificador.trim();

    const formulario = document.getElementById(
        identificadorNormalizado,
    );

    if (!(formulario instanceof HTMLFormElement)) {
        throw new TypeError(
            `Não foi encontrado o formulário "${identificadorNormalizado}".`,
        );
    }

    return formulario;
}

/**
 * Obtém obrigatoriamente um elemento através do identificador HTML.
 *
 * @param {string} identificador Identificador do elemento.
 * @param {Function} tipoElemento Tipo esperado.
 * @param {string} descricao Descrição utilizada na mensagem de erro.
 *
 * @returns {Element} Elemento encontrado.
 *
 * @throws {TypeError} Quando o elemento não existe ou possui tipo inválido.
 *
 * @since 2.0.0
 */
function obterElementoObrigatorio(
    identificador,
    tipoElemento,
    descricao,
) {
    const elemento = document.getElementById(
        identificador,
    );

    if (!(elemento instanceof tipoElemento)) {
        throw new TypeError(
            `Não foi encontrado ${descricao} válido.`,
        );
    }

    return elemento;
}

/**
 * Obtém o limite máximo definido num campo textual.
 *
 * @param {string} identificador Identificador HTML do campo.
 * @param {number} valorPredefinido Valor utilizado quando o campo não existe.
 *
 * @returns {number} Limite máximo positivo.
 *
 * @since 2.0.0
 */
function obterComprimentoMaximo(
    identificador,
    valorPredefinido,
) {
    return obterComprimentoMaximoCampo(
        document.getElementById(
            identificador,
        ),
        valorPredefinido,
    );
}

/**
 * Obtém o limite máximo de um campo textual já encontrado.
 *
 * @param {Element|null} campo Campo recebido.
 * @param {number} valorPredefinido Valor utilizado quando não existe limite.
 *
 * @returns {number} Limite máximo positivo.
 *
 * @since 2.0.0
 */
function obterComprimentoMaximoCampo(
    campo,
    valorPredefinido,
) {
    if (
        (
            campo instanceof HTMLInputElement
            || campo instanceof HTMLTextAreaElement
        )
        && Number.isInteger(campo.maxLength)
        && campo.maxLength > 0
    ) {
        return campo.maxLength;
    }

    return valorPredefinido;
}

/**
 * Valida se todos os valores de uma seleção são identificadores positivos.
 *
 * @param {object} contexto Contexto fornecido pelo validador.
 * @param {unknown} contexto.valor Valor do campo.
 *
 * @returns {true|string} Verdadeiro ou mensagem de erro.
 *
 * @since 2.0.0
 */
function validarListaIdentificadores({
    valor,
}) {
    if (
        Array.isArray(valor)
        && valor.length > 0
        && valor.every(
            (identificador) => (
                typeof identificador === 'string'
                && /^[1-9]\d*$/u.test(
                    identificador,
                )
            ),
        )
    ) {
        return true;
    }

    return 'A seleção contém um identificador inválido.';
}

/**
 * Cria uma regra que valida o intervalo numérico definido num campo.
 *
 * @param {HTMLInputElement} campo Campo numérico.
 * @param {string} mensagem Mensagem apresentada quando o valor está fora do
 *     intervalo.
 *
 * @returns {Function} Regra de validação.
 *
 * @since 2.0.0
 */
function criarRegraIntervaloNumerico(
    campo,
    mensagem,
) {
    const minimoRecebido = campo.min === ''
        ? null
        : Number(campo.min);

    const maximoRecebido = campo.max === ''
        ? null
        : Number(campo.max);

    const minimo = Number.isFinite(
        minimoRecebido,
    )
        ? minimoRecebido
        : null;

    const maximo = Number.isFinite(
        maximoRecebido,
    )
        ? maximoRecebido
        : null;

    return ({ valor }) => {
        const numero = typeof valor === 'string'
            ? Number(valor)
            : Number.NaN;

        if (!Number.isInteger(numero)) {
            return true;
        }

        if (
            (minimo !== null && numero < minimo)
            || (maximo !== null && numero > maximo)
        ) {
            return mensagem;
        }

        return true;
    };
}

/**
 * Apresenta ou remove o erro geral das secções.
 *
 * @param {HTMLElement} elemento Elemento de feedback.
 * @param {string} mensagem Mensagem apresentada.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function atualizarErroSeccoes(
    elemento,
    mensagem,
) {
    const possuiErro = mensagem !== '';

    elemento.textContent = mensagem;

    elemento.classList.toggle(
        'd-block',
        possuiErro,
    );

    elemento.hidden = !possuiErro;
}

/**
 * Valida um campo pertencente a uma secção.
 *
 * @param {ValidadorFormulario} validador Validador principal.
 * @param {Element|null} campo Campo validado.
 * @param {Array<string|Function>} regras Regras aplicáveis.
 * @param {Record<string, string>} mensagens Mensagens das regras.
 *
 * @returns {boolean} Verdadeiro quando o campo existe e é válido.
 *
 * @since 2.0.0
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
 * Determina se o tipo selecionado exige os campos de detalhe.
 *
 * @param {HTMLSelectElement} selecaoTipo Seleção do tipo de secção.
 *
 * @returns {boolean} Verdadeiro quando os detalhes são obrigatórios.
 *
 * @since 2.0.0
 */
function seccaoExigeDetalhes(
    selecaoTipo,
) {
    return selecaoTipo.selectedOptions
        .item(0)
        ?.dataset
        .exigeDetalhes === 'true';
}

/**
 * Valida uma secção do formulário.
 *
 * @param {ValidadorFormulario} validador Validador principal.
 * @param {HTMLElement} seccao Secção validada.
 *
 * @returns {boolean} Verdadeiro quando a secção é válida.
 *
 * @since 2.0.0
 */
function validarSeccao(
    validador,
    seccao,
) {
    const selecaoTipo = seccao.querySelector(
        '.seletor-tipo-seccao',
    );

    const descricao = seccao.querySelector(
        '[name$="[descricao]"]',
    );

    const tipoValido = validarCampoSeccao(
        validador,
        selecaoTipo,
        [
            'obrigatorio',
            'inteiro',
        ],
        {
            obrigatorio:
                'Por favor, seleciona o tipo de secção.',

            inteiro:
                'O tipo de secção selecionado não é válido.',
        },
    );

    const descricaoValida = validarCampoSeccao(
        validador,
        descricao,
        [
            'obrigatorio',
            `maximo:${obterComprimentoMaximoCampo(
                descricao,
                65535,
            )}`,
        ],
        {
            obrigatorio:
                'Por favor, insere a descrição.',

            maximo:
                'A descrição excede o comprimento máximo permitido.',
        },
    );

    let seccaoValida = tipoValido
        && descricaoValida;

    if (
        !(selecaoTipo instanceof HTMLSelectElement)
        || !seccaoExigeDetalhes(
            selecaoTipo,
        )
    ) {
        return seccaoValida;
    }

    const artista = seccao.querySelector(
        '[name$="[artista_id]"]',
    );

    const titulo = seccao.querySelector(
        '[name$="[titulo]"]',
    );

    const ligacao = seccao.querySelector(
        '[name$="[ligacao]"]',
    );

    const tipoIncorporacao = seccao.querySelector(
        '[name$="[tipo_incorporacao]"]',
    );

    const ano = seccao.querySelector(
        '[name$="[ano]"]',
    );

    const validacoes = [
        validarCampoSeccao(
            validador,
            artista,
            [
                'obrigatorio',
                'inteiro',
            ],
            {
                obrigatorio:
                    'Por favor, seleciona o artista.',

                inteiro:
                    'O artista selecionado não é válido.',
            },
        ),

        validarCampoSeccao(
            validador,
            titulo,
            [
                'obrigatorio',
                `maximo:${obterComprimentoMaximoCampo(
                    titulo,
                    255,
                )}`,
            ],
            {
                obrigatorio:
                    'Por favor, insere o título.',

                maximo:
                    'O título excede o comprimento máximo permitido.',
            },
        ),

        validarCampoSeccao(
            validador,
            ligacao,
            [
                'obrigatorio',
                `maximo:${obterComprimentoMaximoCampo(
                    ligacao,
                    2048,
                )}`,
            ],
            {
                obrigatorio:
                    'Por favor, insere a ligação.',

                maximo:
                    'A ligação excede o comprimento máximo permitido.',
            },
        ),

        validarCampoSeccao(
            validador,
            tipoIncorporacao,
            [
                'obrigatorio',

                ({ valor }) => (
                    typeof valor === 'string'
                    && TIPOS_INCORPORACAO.includes(
                        valor,
                    )
                        ? true
                        : 'O tipo de incorporação selecionado não é válido.'
                ),
            ],
            {
                obrigatorio:
                    'Por favor, seleciona o tipo de incorporação.',
            },
        ),

        validarCampoSeccao(
            validador,
            ano,
            ano instanceof HTMLInputElement
                ? [
                    'obrigatorio',
                    'inteiro',

                    criarRegraIntervaloNumerico(
                        ano,
                        'O ano indicado não pertence ao intervalo permitido.',
                    ),
                ]
                : [
                    'obrigatorio',
                    'inteiro',
                ],
            {
                obrigatorio:
                    'Por favor, insere o ano.',

                inteiro:
                    'O ano deve ser um número inteiro.',
            },
        ),
    ];

    if (
        validacoes.some(
            (resultado) => !resultado,
        )
    ) {
        seccaoValida = false;
    }

    return seccaoValida;
}

/**
 * Valida todas as secções do formulário.
 *
 * @param {ValidadorFormulario} validador Validador principal.
 * @param {HTMLElement} contentorSeccoes Contentor das secções.
 * @param {HTMLElement} elementoFeedback Elemento do erro geral.
 *
 * @returns {boolean} Verdadeiro quando todas as secções são válidas.
 *
 * @since 2.0.0
 */
function validarSeccoes(
    validador,
    contentorSeccoes,
    elementoFeedback,
) {
    const seccoes = Array.from(
        contentorSeccoes.querySelectorAll(
            '.item-seccao',
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
        if (
            !validarSeccao(
                validador,
                seccao,
            )
        ) {
            todasSeccoesValidas = false;
        }
    });

    return todasSeccoesValidas;
}

/**
 * Revalida uma secção depois de a validação personalizada ter sido ativada.
 *
 * @param {HTMLFormElement} formulario Formulário principal.
 * @param {ValidadorFormulario} validador Validador principal.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function configurarValidacaoTempoRealSeccoes(
    formulario,
    validador,
) {
    let validacaoAtiva = false;

    const revalidarSeccao = (evento) => {
        if (
            !validacaoAtiva
            || !(evento.target instanceof Element)
        ) {
            return;
        }

        const seccao = evento.target.closest(
            '.item-seccao',
        );

        if (
            !(seccao instanceof HTMLElement)
            || !formulario.contains(
                seccao,
            )
        ) {
            return;
        }

        validarSeccao(
            validador,
            seccao,
        );
    };

    formulario.addEventListener(
        'submit',
        (evento) => {
            if (
                evento instanceof SubmitEvent
                && evento.submitter instanceof HTMLElement
                && evento.submitter.hasAttribute('formnovalidate')
            ) {
                return;
            }

            validacaoAtiva = true;
        },
    );

    formulario.addEventListener(
        'reset',
        () => {
            validacaoAtiva = false;
        },
    );

    formulario.addEventListener(
        'input',
        revalidarSeccao,
    );

    formulario.addEventListener(
        'change',
        revalidarSeccao,
    );
}

/**
 * Inicializa o testador de incorporação de uma secção.
 *
 * @param {HTMLElement} seccao Secção inicializada.
 * @param {Array<object>} fornecedoresIncorporacao Definições recebidas.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function inicializarTestadorIncorporacao(
    seccao,
    fornecedoresIncorporacao,
) {
    new TestadorIncorporacao(
        seccao,
        fornecedoresIncorporacao,
    );
}

/**
 * Inicializa os tooltips pertencentes a uma secção criada dinamicamente.
 *
 * @param {HTMLElement} seccao Secção criada.
 * @param {InicializadorTooltips} inicializadorTooltips Inicializador dos
 *     tooltips.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function inicializarTooltipsSeccao(
    seccao,
    inicializadorTooltips,
) {
    seccao.querySelectorAll(
        '[data-bs-toggle="tooltip"]',
    ).forEach((elemento) => {
        if (elemento instanceof HTMLElement) {
            inicializadorTooltips.inicializarElemento(
                elemento,
            );
        }
    });
}

/**
 * Inicializa os componentes de uma secção criada dinamicamente.
 *
 * @param {HTMLElement} seccao Secção criada.
 * @param {InicializadorTomSelect} inicializadorTomSelect Inicializador dos
 *     campos Tom Select.
 * @param {InicializadorTooltips} inicializadorTooltips Inicializador dos
 *     tooltips.
 * @param {Array<object>} fornecedoresIncorporacao Definições das
 *     incorporações.
 * @param {Map<number, string>} artistasCriados Artistas criados na página.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function inicializarNovaSeccao(
    seccao,
    inicializadorTomSelect,
    inicializadorTooltips,
    fornecedoresIncorporacao,
    artistasCriados,
) {
    inicializadorTomSelect.iniciarTodos(
        seccao,
    );

    const selecaoArtista = seccao.querySelector(
        '.tom-select-artistas',
    );

    if (
        selecaoArtista
        instanceof HTMLSelectElement
    ) {
        artistasCriados.forEach(
            (
                nome,
                identificador,
            ) => {
                adicionarOpcaoTomSelect(
                    selecaoArtista.tomselect
                    ?? null,
                    identificador,
                    nome,
                );
            },
        );
    }

    inicializarTestadorIncorporacao(
        seccao,
        fornecedoresIncorporacao,
    );

    inicializarTooltipsSeccao(
        seccao,
        inicializadorTooltips,
    );
}

/**
 * Cria as configurações dos formulários apresentados em modais.
 *
 * @param {InicializadorTomSelect} inicializadorTomSelect Inicializador dos
 *     campos Tom Select.
 * @param {Readonly<Record<string, string>>} enderecos Endereços validados.
 * @param {Map<number, string>} artistasCriados Artistas criados na página.
 * @param {GestorEdicaoMetalThursday} gestorEdicao Gestor da edição por data.
 * @param {object} contextoCriacaoRapida Contexto dos campos que originaram
 *     as criações rápidas.
 *
 * @returns {Array<object>} Configurações dos formulários.
 *
 * @since 2.0.0
 */
function criarConfiguracoesModais(
    inicializadorTomSelect,
    enderecos,
    artistasCriados,
    gestorEdicao,
    contextoCriacaoRapida,
) {
    const maximoNomeEdicao = obterComprimentoMaximo(
        'nome-nova-edicao',
        255,
    );

    const maximoNomeArtista = obterComprimentoMaximo(
        'nome-novo-artista',
        255,
    );

    const maximoNomeGenero = obterComprimentoMaximo(
        'nome-novo-genero',
        100,
    );

    return [
        {
            idFormulario:
                'formulario-criar-edicao',

            url:
                enderecos.guardarEdicao,

            regrasValidacao: {
                nome: [
                    'obrigatorio',
                    `maximo:${maximoNomeEdicao}`,
                ],

                data_inicio: [
                    'obrigatorio',
                    'data',
                ],

                data_fim: [
                    'data',
                    'posterior_ou_igual:data_inicio',
                ],
            },

            mensagensValidacao: {
                nome: {
                    obrigatorio:
                        'Por favor, insere o nome da edição.',

                    maximo:
                        `O nome da edição não pode ter mais de ${maximoNomeEdicao} caracteres.`,
                },

                data_inicio: {
                    obrigatorio:
                        'Por favor, insere a data de início.',

                    data:
                        'A data de início deve ser válida.',
                },

                data_fim: {
                    data:
                        'A data de fim deve ser válida.',

                    posterior_ou_igual:
                        'A data de fim não pode ser anterior à data de início.',
                },
            },

            aoSucesso: (
                dadosResposta,
            ) => {
                if (
                    !eObjeto(dadosResposta)
                    || !eObjeto(
                        dadosResposta.edicao,
                    )
                ) {
                    return;
                }

                gestorEdicao.adicionarEdicao(
                    dadosResposta.edicao,
                );
            },
        },
        {
            idFormulario:
                'formulario-criar-artista',

            url:
                enderecos.guardarArtista,

            regrasValidacao: {
                nome: [
                    'obrigatorio',
                    `maximo:${maximoNomeArtista}`,
                ],

                origem_geografica_id: [
                    'obrigatorio',
                    'inteiro',
                ],

                'generos[]': [
                    'obrigatorio',
                    validarListaIdentificadores,
                ],
            },

            mensagensValidacao: {
                nome: {
                    obrigatorio:
                        'Por favor, insere o nome do artista.',

                    maximo:
                        `O nome do artista não pode ter mais de ${maximoNomeArtista} caracteres.`,
                },

                origem_geografica_id: {
                    obrigatorio:
                        'Por favor, seleciona a origem geográfica.',

                    inteiro:
                        'A origem geográfica selecionada não é válida.',
                },

                'generos[]': {
                    obrigatorio:
                        'Por favor, seleciona, pelo menos, um género.',
                },
            },

            aoSucesso: (
                dadosResposta,
            ) => {
                const opcao = obterOpcaoResposta(
                    dadosResposta,
                    'artista',
                    'nome',
                );

                if (opcao === null) {
                    return;
                }

                artistasCriados.set(
                    opcao.identificador,
                    opcao.nome,
                );

                document
                    .querySelectorAll(
                        '.tom-select-artistas',
                    )
                    .forEach(
                        (selecao) => {
                            if (
                                selecao
                                instanceof HTMLSelectElement
                            ) {
                                adicionarOpcaoTomSelect(
                                    selecao.tomselect
                                    ?? null,
                                    opcao.identificador,
                                    opcao.nome,
                                    selecao
                                    === contextoCriacaoRapida
                                        .selecaoArtistaDestino,
                                );
                            }
                        },
                    );

                contextoCriacaoRapida
                    .selecaoArtistaDestino =
                    null;
            },
        },
        {
            idFormulario:
                'formulario-criar-genero',

            url:
                enderecos.guardarGenero,

            regrasValidacao: {
                nome: [
                    'obrigatorio',
                    `maximo:${maximoNomeGenero}`,
                ],
            },

            mensagensValidacao: {
                nome: {
                    obrigatorio:
                        'Por favor, insere o nome do género.',

                    maximo:
                        `O nome do género não pode ter mais de ${maximoNomeGenero} caracteres.`,
                },
            },

            aoSucesso: (
                dadosResposta,
            ) => {
                const opcao = obterOpcaoResposta(
                    dadosResposta,
                    'genero',
                    'nome',
                );

                if (opcao === null) {
                    return;
                }

                [
                    'generos-novo-artista',
                    'generos-pai-novo-genero',
                ].forEach(
                    (
                        identificadorCampo,
                    ) => {
                        adicionarOpcaoTomSelect(
                            inicializadorTomSelect
                                .obterInstancia(
                                    identificadorCampo,
                                ),
                            opcao.identificador,
                            opcao.nome,
                            identificadorCampo
                                === 'generos-novo-artista'
                            && contextoCriacaoRapida
                                .generoParaArtista,
                        );
                    },
                );
            },
        },
    ];
}

/**
 * Guarda o campo de artista que originou a abertura do modal de criação.
 *
 * A referência é preservada quando o modal do artista é reaberto
 * programaticamente depois da criação de um género.
 *
 * @param {object} contextoCriacaoRapida Contexto partilhado das criações.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function configurarDestinoCriacaoArtista(
    contextoCriacaoRapida,
) {
    const modalArtista =
        document.getElementById(
            'modal-criar-artista',
        );

    if (!(modalArtista instanceof HTMLElement)) {
        return;
    }

    modalArtista.addEventListener(
        'show.bs.modal',
        (evento) => {
            const acionador =
                evento.relatedTarget;

            if (!(acionador instanceof Element)) {
                return;
            }

            contextoCriacaoRapida
                .selecaoArtistaDestino =
                null;

            const seccao =
                acionador.closest(
                    '.item-seccao',
                );

            if (!(seccao instanceof HTMLElement)) {
                return;
            }

            const selecaoArtista =
                seccao.querySelector(
                    '.tom-select-artistas',
                );

            if (
                selecaoArtista
                instanceof HTMLSelectElement
            ) {
                contextoCriacaoRapida
                    .selecaoArtistaDestino =
                    selecaoArtista;
            }
        },
    );
}

/**
 * Mantém o formulário do artista ao criar um género a partir do respetivo
 * modal e reabre o modal do artista quando o modal do género é fechado.
 *
 * @param {object} contextoCriacaoRapida Contexto partilhado das criações.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function configurarRetornoCriacaoGeneroParaArtista(
    contextoCriacaoRapida,
) {
    const modalArtista =
        document.getElementById(
            'modal-criar-artista',
        );

    const modalGenero =
        document.getElementById(
            'modal-criar-genero',
        );

    if (
        !(modalArtista instanceof HTMLElement)
        || !(modalGenero instanceof HTMLElement)
    ) {
        return;
    }

    let regressarAoModalArtista =
        false;

    modalGenero.addEventListener(
        'show.bs.modal',
        (evento) => {
            const acionador =
                evento.relatedTarget;

            regressarAoModalArtista =
                acionador instanceof Element
                && acionador.closest(
                    '#modal-criar-artista',
                ) === modalArtista;

            contextoCriacaoRapida
                .generoParaArtista =
                regressarAoModalArtista;

            if (!regressarAoModalArtista) {
                return;
            }

            modalArtista.setAttribute(
                'data-preservar-formularios-ao-fechar',
                '',
            );
        },
    );

    modalGenero.addEventListener(
        'hidden.bs.modal',
        () => {
            if (!regressarAoModalArtista) {
                return;
            }

            regressarAoModalArtista =
                false;

            contextoCriacaoRapida
                .generoParaArtista =
                false;

            Modal
                .getOrCreateInstance(
                    modalArtista,
                )
                .show();
        },
    );
}

/**
 * Inicializa um formulário de criação ou edição de MetalThursday.
 *
 * @param {string} identificadorFormulario Identificador HTML do formulário
 *     principal.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function inicializarFormularioMetalThursday(
    identificadorFormulario,
) {
    const formulario = obterFormulario(
        identificadorFormulario,
    );

    const configuracao = obterConfiguracaoFormulario();

    const gestorEdicao = new GestorEdicaoMetalThursday({
        campoData: obterElementoObrigatorio(
            'data-metal-thursday',
            HTMLInputElement,
            'o campo da data da MetalThursday',
        ),

        campoEdicao: obterElementoObrigatorio(
            'edicao-metal-thursday',
            HTMLInputElement,
            'o campo informativo da edição',
        ),

        elementoEstado: obterElementoObrigatorio(
            'estado-edicao-metal-thursday',
            HTMLElement,
            'o elemento de estado da edição',
        ),

        contentorDados: obterElementoObrigatorio(
            'dados-edicoes-metal-thursday',
            HTMLElement,
            'o contentor dos dados das edições',
        ),
    });

    const contentorSeccoes = obterElementoObrigatorio(
        'contentor-seccoes',
        HTMLElement,
        'o contentor das secções',
    );

    const elementoErroSeccoes = obterElementoObrigatorio(
        'erro-seccoes',
        HTMLElement,
        'o elemento de erro das secções',
    );

    const artistasCriados = new Map();

    const contextoCriacaoRapida = {
        selecaoArtistaDestino: null,
        generoParaArtista: false,
    };

    /*
     * Ambos os inicializadores fazem um único passe global no respetivo
     * construtor. As secções já existentes não devem voltar a ser pesquisadas
     * individualmente por estes componentes.
     */
    const inicializadorTomSelect =
        new InicializadorTomSelect();

    const inicializadorTooltips =
        new InicializadorTooltips();

    const campoProximoNomeado =
        formulario.querySelector(
            '[name="proximo_nomeado_id"]',
        );

    const possuiSelecaoProximoNomeado =
        campoProximoNomeado
        instanceof HTMLSelectElement;

    /*
     * O testador de incorporação é específico de cada secção e, por isso,
     * continua a necessitar de uma instância por item.
     */
    contentorSeccoes
        .querySelectorAll(
            '.item-seccao',
        )
        .forEach(
            (seccao) => {
                if (
                    seccao
                    instanceof HTMLElement
                ) {
                    inicializarTestadorIncorporacao(
                        seccao,
                        configuracao
                            .fornecedoresIncorporacao,
                    );
                }
            },
        );

    new GestorSeccoes(
        '#contentor-seccoes',
        '#botao-adicionar-seccao',
        '#modelo-item-seccao',
        (novaSeccao) => {
            inicializarNovaSeccao(
                novaSeccao,
                inicializadorTomSelect,
                inicializadorTooltips,
                configuracao
                    .fornecedoresIncorporacao,
                artistasCriados,
            );

            atualizarErroSeccoes(
                elementoErroSeccoes,
                '',
            );
        },
    );

    if (possuiSelecaoProximoNomeado) {
        new SeletorNomeados({
            seletorBotaoAleatorio:
                '#botao-selecionar-nomeado-aleatorio',

            seletorBotaoMaisAntigo:
                '#botao-selecionar-nomeado-mais-antigo',

            instanciaTomSelect:
                inicializadorTomSelect
                    .obterInstancia(
                        'proximo-nomeado-metal-thursday',
                    ),

            urlNomeadoMaisAntigo:
                configuracao
                    .enderecos
                    .obterUtilizadorHaMaisTempoSemNomeacao,

            obterValorExcluido: () => {
                const campoAutor =
                    formulario.querySelector(
                        '[name="autor_id"]',
                    );

                if (
                    campoAutor instanceof HTMLInputElement
                    || campoAutor instanceof HTMLSelectElement
                ) {
                    return campoAutor.value;
                }

                return null;
            },
        });
    }

    const maximoNomeMetalThursday = obterComprimentoMaximo(
        'nome-metal-thursday',
        255,
    );

    const validadorFormulario =
        new ValidadorFormulario(
            formulario,
            {
                regras: {
                    data: [
                        'obrigatorio',
                        'data',

                        ({ valor }) =>
                            gestorEdicao.validarData(
                                valor,
                            ),
                    ],

                    nome: [
                        `maximo:${maximoNomeMetalThursday}`,
                    ],

                    autor_id: [
                        'obrigatorio',
                        'inteiro',
                    ],

                    ...(possuiSelecaoProximoNomeado
                        ? {
                            proximo_nomeado_id: [
                                'obrigatorio',
                                'inteiro',
                                'diferente:autor_id',
                            ],
                        }
                        : {}),
                },

                mensagens: {
                    data: {
                        obrigatorio:
                            'Por favor, seleciona a data.',

                        data:
                            'A data deve ser válida.',
                    },

                    nome: {
                        maximo:
                            `O nome não pode ter mais de ${maximoNomeMetalThursday} caracteres.`,
                    },

                    autor_id: {
                        obrigatorio:
                            'Por favor, seleciona o autor.',

                        inteiro:
                            'O autor selecionado não é válido.',
                    },

                    ...(possuiSelecaoProximoNomeado
                        ? {
                            proximo_nomeado_id: {
                                obrigatorio:
                                    'Por favor, seleciona o próximo nomeado.',

                                inteiro:
                                    'O próximo nomeado selecionado não é válido.',

                                diferente:
                                    'O próximo nomeado deve ser diferente do autor.',
                            },
                        }
                        : {}),
                },

                validadorPersonalizado: (
                    validador,
                ) => validarSeccoes(
                    validador,
                    contentorSeccoes,
                    elementoErroSeccoes,
                ),
            },
        );

    configurarValidacaoTempoRealSeccoes(
        formulario,
        validadorFormulario,
    );

    configurarDestinoCriacaoArtista(
        contextoCriacaoRapida,
    );

    configurarRetornoCriacaoGeneroParaArtista(
        contextoCriacaoRapida,
    );

    new GestorFormulariosModais(
        criarConfiguracoesModais(
            inicializadorTomSelect,
            configuracao.enderecos,
            artistasCriados,
            gestorEdicao,
            contextoCriacaoRapida,
        ),
    );
}

export default inicializarFormularioMetalThursday;

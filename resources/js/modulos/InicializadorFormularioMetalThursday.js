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
* @since 3.0.0
* @version 1.0.0
*/

/**
* Endereços obrigatórios da configuração preparada pelo servidor.
*
* @type {ReadonlyArray<string>}
    *
    * @since 3.0.0
    * @version 1.0.0
    */
    const CHAVES_ENDERECOS = Object.freeze([
    'guardarEdicao',
    'guardarBanda',
    'guardarGenero',
    'obterUtilizadorHaMaisTempoSemNomeacao',
    ]);

    /**
    * Tipos de incorporação permitidos nas secções.
    *
    * @type {ReadonlyArray<string>}
        *
        * @since 3.0.0
        * @version 1.0.0
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
        * @returns {boolean} Verdadeiro quando o valor é um objeto.
        *
        * @since 3.0.0
        * @version 1.0.0
        */
        function eObjeto(valor) {
        return typeof valor === 'object'
        && valor !== null
        && !Array.isArray(valor);
        }

        /**
        * Obtém e valida a configuração global do formulário.
        *
        * @returns {{
 *     enderecos: Record<string, string>,
 *     fornecedoresIncorporacao: Array<object>
 * }} Configuração validada.
        *
        * @throws {TypeError} Quando a configuração é inválida.
        *
        * @since 3.0.0
        * @version 1.0.0
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

        CHAVES_ENDERECOS.forEach((chave) => {
        const endereco =
        configuracao.enderecos[chave];

        if (
        typeof endereco !== 'string'
        || endereco.trim() === ''
        ) {
        throw new TypeError(
        `O endereço "${chave}" do formulário de MetalThursday é inválido.`,
        );
        }
        });

        return {
        enderecos: configuracao.enderecos,
        fornecedoresIncorporacao:
        configuracao.fornecedoresIncorporacao,
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
        * @since 3.0.0
        * @version 1.0.0
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

        const formulario =
        document.getElementById(
        identificador.trim(),
        );

        if (!(formulario instanceof HTMLFormElement)) {
        throw new TypeError(
        `Não foi encontrado o formulário "${identificador}".`,
        );
        }

        return formulario;
        }

        /**
        * Obtém o limite máximo definido num campo textual.
        *
        * @param {string} identificador Identificador HTML do campo.
        * @param {number} valorPredefinido Valor utilizado quando o campo não existe.
        *
        * @returns {number} Limite máximo positivo.
        *
        * @since 3.0.0
        * @version 1.0.0
        */
        function obterComprimentoMaximo(
        identificador,
        valorPredefinido,
        ) {
        const campo =
        document.getElementById(
        identificador,
        );

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
        * Obtém o limite máximo de um campo textual já encontrado.
        *
        * @param {Element|null} campo Campo recebido.
        * @param {number} valorPredefinido Valor utilizado quando não existe limite.
        *
        * @returns {number} Limite máximo positivo.
        *
        * @since 3.0.0
        * @version 1.0.0
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
        * @since 3.0.0
        * @version 1.0.0
        */
        function validarListaIdentificadores({
        valor,
        }) {
        if (
        Array.isArray(valor)
        && valor.every(
        (identificador) => (
        typeof identificador === 'string'
        && /^[1-9]\d*$/.test(
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
        * intervalo.
        *
        * @returns {Function} Regra de validação.
        *
        * @since 3.0.0
        * @version 1.0.0
        */
        function criarRegraIntervaloNumerico(
        campo,
        mensagem,
        ) {
        const minimo =
        campo.min === ''
        ? null
        : Number(campo.min);

        const maximo =
        campo.max === ''
        ? null
        : Number(campo.max);

        return ({ valor }) => {
        const numero =
        typeof valor === 'string'
        ? Number(valor)
        : Number.NaN;

        if (!Number.isInteger(numero)) {
        return true;
        }

        if (
        (minimo !== null && numero < minimo)
            || (maximo !==null && numero> maximo)
            ) {
            return mensagem;
            }

            return true;
            };
            }

            /**
            * Apresenta ou remove o erro geral das secções.
            *
            * @param {HTMLElement|null} elemento Elemento de feedback.
            * @param {string} mensagem Mensagem a apresentar.
            *
            * @returns {void}
            *
            * @since 2.0.0
            * @version 2.0.0
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
            * @param {ValidadorFormulario} validador Validador principal.
            * @param {Element|null} campo Campo a validar.
            * @param {Array<string|Function>} regras Regras aplicáveis.
                * @param {Object<string, string>} mensagens Mensagens das regras.
                    *
                    * @returns {boolean} Verdadeiro quando o campo existe e é válido.
                    *
                    * @since 2.0.0
                    * @version 2.0.0
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
                    * Valida todas as secções do formulário.
                    *
                    * @param {ValidadorFormulario} validador Validador principal.
                    *
                    * @returns {boolean} Verdadeiro quando todas as secções são válidas.
                    *
                    * @since 2.0.0
                    * @version 3.0.0
                    */
                    function validarSeccoes(validador) {
                    const contentorSeccoes =
                    document.getElementById(
                    'contentor-seccoes',
                    );

                    const elementoFeedback =
                    document.getElementById(
                    'erro-seccoes',
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

                    let todasSeccoesValidas =
                    true;

                    seccoes.forEach((seccao) => {
                    const selecaoTipo =
                    seccao.querySelector(
                    '.seletor-tipo-seccao',
                    );

                    const descricao =
                    seccao.querySelector(
                    '[name$="[descricao]"]',
                    );

                    const tipoValido =
                    validarCampoSeccao(
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

                    const descricaoValida =
                    validarCampoSeccao(
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

                    if (!tipoValido || !descricaoValida) {
                    todasSeccoesValidas =
                    false;
                    }

                    if (!(selecaoTipo instanceof HTMLSelectElement)) {
                    todasSeccoesValidas =
                    false;

                    return;
                    }

                    const opcaoSelecionada =
                    selecaoTipo.options[
                    selecaoTipo.selectedIndex
                    ]
                    ?? null;

                    const exigeDetalhes =
                    opcaoSelecionada
                    ?.dataset
                    .exigeDetalhes
                    === 'true';

                    if (!exigeDetalhes) {
                    return;
                    }

                    const banda =
                    seccao.querySelector(
                    '[name$="[banda_id]"]',
                    );

                    const titulo =
                    seccao.querySelector(
                    '[name$="[titulo]"]',
                    );

                    const ligacao =
                    seccao.querySelector(
                    '[name$="[ligacao]"]',
                    );

                    const tipoIncorporacao =
                    seccao.querySelector(
                    '[name$="[tipo_incorporacao]"]',
                    );

                    const ano =
                    seccao.querySelector(
                    '[name$="[ano]"]',
                    );

                    const validacoes = [
                    validarCampoSeccao(
                    validador,
                    banda,
                    [
                    'obrigatorio',
                    'inteiro',
                    ],
                    {
                    obrigatorio:
                    'Por favor, seleciona a banda.',

                    inteiro:
                    'A banda selecionada não é válida.',
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
                    && TIPOS_INCORPORACAO.includes(valor)
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
                    todasSeccoesValidas =
                    false;
                    }
                    });

                    return todasSeccoesValidas;
                    }

                    /**
                    * Adiciona ou atualiza uma opção numa instância Tom Select.
                    *
                    * @param {object|null} instancia Instância Tom Select.
                    * @param {unknown} identificador Identificador da opção.
                    * @param {unknown} nome Texto apresentado.
                    * @param {boolean} selecionar Indica se a opção deve ficar selecionada.
                    *
                    * @returns {boolean} Indica se a opção foi adicionada ou atualizada.
                    *
                    * @since 2.0.0
                    * @version 2.0.0
                    */
                    function adicionarOpcaoTomSelect(
                    instancia,
                    identificador,
                    nome,
                    selecionar = false,
                    ) {
                    if (
                    !instancia
                    || typeof instancia.addOption !== 'function'
                    || !Number.isInteger(identificador)
                    || identificador < 1
                        || typeof nome !=='string'
                        || nome.trim()===''
                        ) {
                        return false;
                        }

                        const valor=String(identificador);

                        const opcao={
                        value: valor,
                        text: nome.trim(),
                        };

                        if (
                        eObjeto(instancia.options)
                        && Object.hasOwn(
                        instancia.options,
                        valor,
                        )
                        && typeof instancia.updateOption==='function'
                        ) {
                        instancia.updateOption(
                        valor,
                        opcao,
                        );
                        } else {
                        instancia.addOption(
                        opcao,
                        );
                        }

                        if (
                        typeof instancia.refreshOptions==='function'
                        ) {
                        instancia.refreshOptions(
                        false,
                        );
                        }

                        if (
                        selecionar
                        && typeof instancia.setValue==='function'
                        ) {
                        instancia.setValue(
                        valor,
                        );
                        }

                        return true;
                        }

                        /**
                        * Obtém uma entidade criada a partir de uma resposta AJAX.
                        *
                        * @param {unknown} dadosResposta Resposta recebida.
                        * @param {string} chave Chave da entidade.
                        * @param {string} chaveNome Chave do texto apresentado.
                        *
                        * @returns {{identificador: number, nome: string}|null} Opção criada.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterOpcaoResposta(
    dadosResposta,
    chave,
    chaveNome,
) {
    if (!eObjeto(dadosResposta)) {
        return null;
    }

    const entidade =
        dadosResposta[chave];

    if (!eObjeto(entidade)) {
        return null;
    }

    const identificador =
        entidade.id;

    const nome =
        entidade[chaveNome];

    if (
        !Number.isInteger(identificador)
        || identificador < 1
        || typeof nome !== 'string'
        || nome.trim() === ''
    ) {
        return null;
    }

    return {
        identificador,
        nome: nome.trim(),
    };
}

/**
 * Inicializa os componentes pertencentes a uma secção.
 *
 * @param {HTMLElement} seccao Secção a inicializar.
 * @param {InicializadorTomSelect} inicializadorTomSelect Inicializador dos
 * campos Tom Select.
 * @param {InicializadorTooltips} inicializadorTooltips Inicializador dos
 * tooltips.
 * @param {Array<object>} fornecedoresIncorporacao Definições das
 * incorporações.
 * @param {Map<number, string>} bandasCriadas Bandas criadas na página.
 *
 * @returns {void}
 *
 * @since 2.0.0
 * @version 3.0.0
 */
function inicializarComponentesSeccao(
    seccao,
    inicializadorTomSelect,
    inicializadorTooltips,
    fornecedoresIncorporacao,
    bandasCriadas,
) {
    if (
        !(seccao instanceof HTMLElement)
        || seccao.dataset
            .componentesFormularioInicializados
            === 'true'
    ) {
        return;
    }

    inicializadorTomSelect.iniciarTodos(
        seccao,
    );

    const selecaoBanda =
        seccao.querySelector(
            '.tom-select-bandas',
        );

    if (selecaoBanda instanceof HTMLSelectElement) {
        bandasCriadas.forEach(
            (nome, identificador) => {
                adicionarOpcaoTomSelect(
                    selecaoBanda.tomselect
                    ?? null,
                    identificador,
                    nome,
                );
            },
        );
    }

    new TestadorIncorporacao(
        seccao,
        fornecedoresIncorporacao,
    );

    inicializadorTooltips.atualizar();

    seccao.dataset
        .componentesFormularioInicializados =
            'true';
}

/**
 * Cria as configurações dos formulários apresentados em modais.
 *
 * @param {InicializadorTomSelect} inicializadorTomSelect Inicializador dos
 * campos Tom Select.
 * @param {Record<string, string>} enderecos Endereços preparados pelo
 * servidor.
 * @param {Map<number, string>} bandasCriadas Bandas criadas na página.
 *
 * @returns {Array<object>} Configurações dos formulários.
 *
 * @since 2.0.0
 * @version 3.0.0
 */
function criarConfiguracoesModais(
    inicializadorTomSelect,
    enderecos,
    bandasCriadas,
) {
    const maximoNomeEdicao =
        obterComprimentoMaximo(
            'nome-nova-edicao',
            255,
        );

    const maximoNomeBanda =
        obterComprimentoMaximo(
            'nome-nova-banda',
            255,
        );

    const maximoNomeGenero =
        obterComprimentoMaximo(
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

            aoSucesso: (dadosResposta) => {
                const opcao =
                    obterOpcaoResposta(
                        dadosResposta,
                        'edicao',
                        'texto_apresentacao',
                    );

                if (opcao === null) {
                    return;
                }

                adicionarOpcaoTomSelect(
                    inicializadorTomSelect
                        .obterInstancia(
                            'edicao-metal-thursday',
                        ),
                    opcao.identificador,
                    opcao.nome,
                    true,
                );
            },
        },
        {
            idFormulario:
                'formulario-criar-banda',

            url:
                enderecos.guardarBanda,

            regrasValidacao: {
                nome: [
                    'obrigatorio',
                    `maximo:${maximoNomeBanda}`,
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
                        'Por favor, insere o nome da banda.',

                    maximo:
                        `O nome da banda não pode ter mais de ${maximoNomeBanda} caracteres.`,
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

            aoSucesso: (dadosResposta) => {
                const opcao =
                    obterOpcaoResposta(
                        dadosResposta,
                        'banda',
                        'nome',
                    );

                if (opcao === null) {
                    return;
                }

                bandasCriadas.set(
                    opcao.identificador,
                    opcao.nome,
                );

                document
                    .querySelectorAll(
                        '.tom-select-bandas',
                    )
                    .forEach((selecao) => {
                        if (
                            selecao
                            instanceof HTMLSelectElement
                        ) {
                            adicionarOpcaoTomSelect(
                                selecao.tomselect
                                ?? null,
                                opcao.identificador,
                                opcao.nome,
                            );
                        }
                    });
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

            aoSucesso: (dadosResposta) => {
                const opcao =
                    obterOpcaoResposta(
                        dadosResposta,
                        'genero',
                        'nome',
                    );

                if (opcao === null) {
                    return;
                }

                [
                    'generos-nova-banda',
                    'generos-pai-novo-genero',
                ].forEach((identificadorCampo) => {
                    adicionarOpcaoTomSelect(
                        inicializadorTomSelect
                            .obterInstancia(
                                identificadorCampo,
                            ),
                        opcao.identificador,
                        opcao.nome,
                    );
                });
            },
        },
    ];
}

/**
 * Inicializa um formulário de criação ou edição de MetalThursday.
 *
 * @param {string} identificadorFormulario Identificador HTML do formulário
 * principal.
 *
 * @returns {void}
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function inicializarFormularioMetalThursday(
    identificadorFormulario,
) {
    const formulario =
        obterFormulario(
            identificadorFormulario,
        );

    const configuracao =
        obterConfiguracaoFormulario();

    const bandasCriadas =
        new Map();

    const inicializadorTomSelect =
        new InicializadorTomSelect();

    const inicializadorTooltips =
        new InicializadorTooltips();

    const contentorSeccoes =
        document.getElementById(
            'contentor-seccoes',
        );

    if (contentorSeccoes instanceof HTMLElement) {
        contentorSeccoes
            .querySelectorAll(
                '.item-seccao',
            )
            .forEach((seccao) => {
                if (seccao instanceof HTMLElement) {
                    inicializarComponentesSeccao(
                        seccao,
                        inicializadorTomSelect,
                        inicializadorTooltips,
                        configuracao
                            .fornecedoresIncorporacao,
                        bandasCriadas,
                    );
                }
            });
    }

    new GestorSeccoes(
        '#contentor-seccoes',
        '#botao-adicionar-seccao',
        '#modelo-item-seccao',
        (novaSeccao) => {
            inicializarComponentesSeccao(
                novaSeccao,
                inicializadorTomSelect,
                inicializadorTooltips,
                configuracao
                    .fornecedoresIncorporacao,
                bandasCriadas,
            );
        },
    );

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
    });

    const maximoNomeMetalThursday =
        obterComprimentoMaximo(
            'nome-metal-thursday',
            255,
        );

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                edicao_id: [
                    'obrigatorio',
                    'inteiro',
                ],

                data: [
                    'obrigatorio',
                    'data',
                ],

                nome: [
                    `maximo:${maximoNomeMetalThursday}`,
                ],

                autor_id: [
                    'obrigatorio',
                    'inteiro',
                ],

                proximo_nomeado_id: [
                    'obrigatorio',
                    'inteiro',
                ],
            },

            mensagens: {
                edicao_id: {
                    obrigatorio:
                        'Por favor, seleciona a edição.',

                    inteiro:
                        'A edição selecionada não é válida.',
                },

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

                proximo_nomeado_id: {
                    obrigatorio:
                        'Por favor, seleciona o próximo nomeado.',

                    inteiro:
                        'O próximo nomeado selecionado não é válido.',
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
            configuracao.enderecos,
            bandasCriadas,
        ),
    );
}

export default inicializarFormularioMetalThursday;

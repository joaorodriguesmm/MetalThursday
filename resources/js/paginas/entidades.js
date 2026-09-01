import GestorFormulariosModais
    from '../modulos/GestorFormulariosModais';
import {
    adicionarOpcaoTomSelect,
    obterOpcaoResposta,
} from '../modulos/OpcoesTomSelect';
import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Script específico das páginas de gestão de entidades.
 *
 * Inicializa os campos Tom Select quando existem, configura a validação de
 * apoio dos formulários de artistas, géneros e edições e permite criar géneros
 * sem abandonar o formulário de artistas.
 *
 * @since 1.0.0
 */

/**
 * Seletor dos campos enriquecidos pelo Tom Select.
 *
 * @type {string}
 *
 * @since 2.0.0
 */
const SELETOR_TOM_SELECT =
    '.tom-select-unico, .tom-select-multiplo';

/**
 * Identificadores utilizados pela criação rápida de géneros.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 */
const IDENTIFICADORES_CRIACAO_GENERO = Object.freeze({
    formulario:
        'formulario-criar-genero',

    generosArtista:
        'generos-artista',

    generosPais:
        'generos-pai-novo-genero',
});

/**
 * Configurações dos formulários geridos pelo script.
 *
 * As regras de comprimento do nome são acrescentadas a partir do atributo
 * `maxlength` declarado no próprio campo HTML.
 *
 * @type {ReadonlyArray<object>}
 *
 * @since 2.0.0
 */
const CONFIGURACOES_FORMULARIOS = Object.freeze([
    {
        identificadorFormulario:
            'formulario-artista',

        regras: {
            nome: [
                'obrigatorio',
            ],

            origem_geografica_id: [
                'inteiro',
            ],

            'generos[]': [
                'obrigatorio',
            ],
        },

        mensagens: {
            nome: {
                obrigatorio:
                    'Por favor, insere o nome do artista.',

                maximo:
                    'O nome do artista é demasiado longo.',
            },

            origem_geografica_id: {
                inteiro:
                    'A origem geográfica selecionada não é válida.',
            },

            'generos[]': {
                obrigatorio:
                    'Por favor, seleciona pelo menos um género.',
            },
        },
    },

    {
        identificadorFormulario:
            'formulario-genero',

        regras: {
            nome: [
                'obrigatorio',
            ],
        },

        mensagens: {
            nome: {
                obrigatorio:
                    'Por favor, insere o nome do género.',

                maximo:
                    'O nome do género é demasiado longo.',
            },
        },
    },

    {
        identificadorFormulario:
            'formulario-edicao',

        regras: {
            nome: [
                'obrigatorio',
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

        mensagens: {
            nome: {
                obrigatorio:
                    'Por favor, insere o nome da edição.',

                maximo:
                    'O nome da edição é demasiado longo.',
            },

            data_inicio: {
                obrigatorio:
                    'Por favor, insere a data de início da edição.',

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
    },
]);

/**
 * Obtém o comprimento máximo declarado no campo do nome.
 *
 * @param {HTMLFormElement} formulario Formulário pesquisado.
 *
 * @returns {number} Comprimento máximo positivo.
 *
 * @throws {TypeError} Quando o formulário não possui o contrato esperado.
 *
 * @since 2.0.0
 */
function obterComprimentoMaximoNome(formulario) {
    const campo =
        formulario.elements.namedItem(
            'nome',
        );

    if (
        !(campo instanceof HTMLInputElement)
        || !Number.isInteger(
            campo.maxLength,
        )
        || campo.maxLength <= 0
    ) {
        throw new TypeError(
            `O formulário "${formulario.id}" deve possuir um campo de nome com comprimento máximo válido.`,
        );
    }

    return campo.maxLength;
}

/**
 * Prepara as regras de um formulário a partir do respetivo HTML.
 *
 * @param {HTMLFormElement} formulario Formulário gerido.
 * @param {Record<string, Array<string|Function>>} regrasBase
 *     Regras específicas da entidade.
 *
 * @returns {Record<string, Array<string|Function>>} Regras finais.
 *
 * @since 2.0.0
 */
function criarRegrasFormulario(
    formulario,
    regrasBase,
) {
    return {
        ...regrasBase,

        nome: [
            ...(regrasBase.nome ?? []),

            `maximo:${obterComprimentoMaximoNome(
                formulario,
            )}`,
        ],
    };
}

/**
 * Inicia os validadores dos formulários disponíveis.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarValidadoresEntidades() {
    CONFIGURACOES_FORMULARIOS.forEach(
        ({
            identificadorFormulario,
            regras,
            mensagens,
        }) => {
            const formulario =
                document.getElementById(
                    identificadorFormulario,
                );

            if (!(formulario instanceof HTMLFormElement)) {
                return;
            }

            new ValidadorFormulario(
                formulario,
                {
                    regras:
                        criarRegrasFormulario(
                            formulario,
                            regras,
                        ),

                    mensagens,
                },
            );
        },
    );
}

/**
 * Inicializa o Tom Select apenas quando a página possui campos compatíveis.
 *
 * As páginas de edições utilizam este mesmo entrypoint, mas não possuem
 * campos Tom Select. O carregamento condicional evita transferir essa
 * dependência nessas páginas.
 *
 * @returns {Promise<object|null>} Inicializador criado ou nulo.
 *
 * @since 2.0.0
 */
async function iniciarTomSelectSeNecessario() {
    if (
        document.querySelector(
            SELETOR_TOM_SELECT,
        ) === null
    ) {
        return null;
    }

    const {
        default:
            InicializadorTomSelect,
    } = await import(
        '../modulos/InicializadorTomSelect'
    );

    return new InicializadorTomSelect();
}

/**
 * Inicia a criação rápida de géneros no formulário de artistas.
 *
 * Após a criação, o novo género é acrescentado e selecionado no campo do
 * artista. É também acrescentado à lista de possíveis géneros pais para uma
 * eventual criação seguinte sem recarregar a página.
 *
 * @param {object|null} inicializadorTomSelect Inicializador Tom Select.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function iniciarCriacaoRapidaGenero(
    inicializadorTomSelect,
) {
    const formulario =
        document.getElementById(
            IDENTIFICADORES_CRIACAO_GENERO
                .formulario,
        );

    if (
        !(formulario
            instanceof HTMLFormElement)
        || inicializadorTomSelect === null
        || typeof inicializadorTomSelect
            .obterInstancia !== 'function'
    ) {
        return;
    }

    const endereco =
        formulario.action.trim();

    if (endereco === '') {
        throw new TypeError(
            'O formulário de criação do género não possui um endereço válido.',
        );
    }

    new GestorFormulariosModais([
        {
            idFormulario:
                formulario.id,

            url:
                endereco,

            regrasValidacao:
                criarRegrasFormulario(
                    formulario,
                    {
                        nome: [
                            'obrigatorio',
                        ],
                    },
                ),

            mensagensValidacao: {
                nome: {
                    obrigatorio:
                        'Por favor, insere o nome do género.',

                    maximo:
                        'O nome do género é demasiado longo.',
                },
            },

            aoSucesso: (
                dadosResposta,
            ) => {
                const genero =
                    obterOpcaoResposta(
                        dadosResposta,
                        'genero',
                        'nome',
                    );

                if (genero === null) {
                    throw new TypeError(
                        'A resposta da criação do género é inválida.',
                    );
                }

                const selecaoGenerosArtista =
                    inicializadorTomSelect
                        .obterInstancia(
                            IDENTIFICADORES_CRIACAO_GENERO
                                .generosArtista,
                        );

                if (
                    !adicionarOpcaoTomSelect(
                        selecaoGenerosArtista,
                        genero.identificador,
                        genero.nome,
                        true,
                    )
                ) {
                    throw new TypeError(
                        'Não foi possível atualizar a seleção de géneros do artista.',
                    );
                }

                const selecaoGenerosPais =
                    inicializadorTomSelect
                        .obterInstancia(
                            IDENTIFICADORES_CRIACAO_GENERO
                                .generosPais,
                        );

                if (selecaoGenerosPais !== null) {
                    adicionarOpcaoTomSelect(
                        selecaoGenerosPais,
                        genero.identificador,
                        genero.nome,
                    );
                }
            },
        },
    ]);
}

/**
 * Inicia os comportamentos das páginas de gestão de entidades.
 *
 * @returns {Promise<void>}
 *
 * @since 1.0.0
 */
async function iniciarPaginaEntidades() {
    const inicializadorTomSelect =
        await iniciarTomSelectSeNecessario();

    iniciarValidadoresEntidades();

    iniciarCriacaoRapidaGenero(
        inicializadorTomSelect,
    );
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        () => {
            void iniciarPaginaEntidades();
        },
        {
            once: true,
        },
    );
} else {
    void iniciarPaginaEntidades();
}

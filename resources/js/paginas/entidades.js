import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Script específico das páginas de gestão de entidades.
 *
 * Inicializa os campos Tom Select quando existem e configura a validação de
 * apoio dos formulários de bandas, géneros e edições.
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
            'formulario-banda',

        regras: {
            nome: [
                'obrigatorio',
            ],

            origem_geografica_id: [
                'obrigatorio',
                'inteiro',
            ],

            'generos[]': [
                'obrigatorio',
            ],
        },

        mensagens: {
            nome: {
                obrigatorio:
                    'Por favor, insere o nome da banda.',

                maximo:
                    'O nome da banda é demasiado longo.',
            },

            origem_geografica_id: {
                obrigatorio:
                    'Por favor, seleciona a origem geográfica da banda.',

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
 * @returns {Promise<void>}
 *
 * @since 2.0.0
 */
async function iniciarTomSelectSeNecessario() {
    if (
        document.querySelector(
            SELETOR_TOM_SELECT,
        ) === null
    ) {
        return;
    }

    const {
        default:
            InicializadorTomSelect,
    } = await import(
        '../modulos/InicializadorTomSelect'
    );

    new InicializadorTomSelect();
}

/**
 * Inicia os comportamentos das páginas de gestão de entidades.
 *
 * @returns {Promise<void>}
 *
 * @since 1.0.0
 */
async function iniciarPaginaEntidades() {
    await iniciarTomSelectSeNecessario();

    iniciarValidadoresEntidades();
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

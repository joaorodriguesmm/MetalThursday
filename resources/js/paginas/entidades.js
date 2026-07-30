import InicializadorTomSelect
    from '../modulos/InicializadorTomSelect';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Script específico das páginas de gestão de entidades.
 *
 * Inicializa os campos Tom Select e a validação de apoio dos
 * formulários de bandas, géneros e edições.
 *
 * @since 1.0.0
 * @version 3.0.0
 */

/**
 * Configurações dos formulários geridos pelo script.
 *
 * @type {ReadonlyArray<Object>}
 *
 * @since 2.1.0
 * @version 2.0.0
 */
const CONFIGURACOES_FORMULARIOS = Object.freeze([
    {
        identificadorFormulario:
            'formulario-banda',

        regras: {
            nome: [
                'obrigatorio',
                'maximo:255',
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
                    'O nome da banda não pode ter mais de 255 caracteres.',
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
                'maximo:100',
            ],
        },

        mensagens: {
            nome: {
                obrigatorio:
                    'Por favor, insere o nome do género.',

                maximo:
                    'O nome do género não pode ter mais de 100 caracteres.',
            },
        },
    },

    {
        identificadorFormulario:
            'formulario-edicao',

        regras: {
            nome: [
                'obrigatorio',
                'maximo:255',
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
                    'O nome da edição não pode ter mais de 255 caracteres.',
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
 * Inicia os validadores dos formulários disponíveis.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.3.0
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
                    regras,
                    mensagens,
                },
            );
        },
    );
}

/**
 * Inicia os comportamentos das páginas de gestão de entidades.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.3.0
 */
function iniciarPaginaEntidades() {
    new InicializadorTomSelect();

    iniciarValidadoresEntidades();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaEntidades,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaEntidades();
}

import InicializadorTomSelect from '../modulos/InicializadorTomSelect';
import ValidadorFormulario from '../modulos/ValidadorFormulario';

/**
 * Configura os validadores dos formulários de entidades.
 *
 * Os identificadores dos formulários e os nomes dos campos permanecem
 * temporariamente em inglês por corresponderem aos contratos atuais das
 * views e do servidor.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function inicializarValidadoresEntidades() {
    const configuracoes = [
        {
            idFormulario: 'band-form',

            regras: {
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

            mensagens: {
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
        },
        {
            idFormulario: 'genre-form',

            regras: {
                name: [
                    'obrigatorio',
                    'maximo:255',
                ],
            },

            mensagens: {
                name: {
                    obrigatorio:
                        'Por favor, insere o nome.',

                    maximo:
                        'O nome não pode ter mais de 255 caracteres.',
                },
            },
        },
        {
            idFormulario: 'edition-form',

            regras: {
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

            mensagens: {
                name: {
                    obrigatorio:
                        'Por favor, insere o nome.',

                    maximo:
                        'O nome não pode ter mais de 255 caracteres.',
                },

                start_date: {
                    obrigatorio:
                        'A data de início é obrigatória.',

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
        },
    ];

    configuracoes.forEach(
        ({
            idFormulario,
            regras,
            mensagens,
        }) => {
            const formulario = document.getElementById(
                idFormulario,
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
 * Inicializa os componentes da página de entidades.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function inicializarPaginaEntidades() {
    new InicializadorTomSelect();

    inicializarValidadoresEntidades();
}

document.addEventListener(
    'DOMContentLoaded',
    inicializarPaginaEntidades,
    {
        once: true,
    },
);

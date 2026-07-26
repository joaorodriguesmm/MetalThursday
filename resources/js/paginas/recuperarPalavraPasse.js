import ValidadorFormulario from '../modulos/ValidadorFormulario';

/**
 * Script específico da página de recuperação da palavra-passe.
 *
 * O identificador do formulário e o nome do campo `email` permanecem
 * inalterados por corresponderem aos contratos atuais da vista e da
 * autenticação.
 *
 * @since 1.0.0
 * @version 2.1.0
 */

/**
 * Inicia os comportamentos da página de recuperação da palavra-passe.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.1.0
 */
function iniciarPaginaRecuperacaoPalavraPasse() {
    const formulario = document.getElementById(
        'forgot-password-form',
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                email: [
                    'obrigatorio',
                    'email',
                    'maximo:255',
                ],
            },

            mensagens: {
                email: {
                    obrigatorio:
                        'Por favor, insere o teu endereço de e-mail.',

                    email:
                        'Por favor, insere um endereço de e-mail válido.',

                    maximo:
                        'O endereço de e-mail não pode ter mais de 255 caracteres.',
                },
            },
        },
    );
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaRecuperacaoPalavraPasse,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaRecuperacaoPalavraPasse();
}

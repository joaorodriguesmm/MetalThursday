import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Script específico da página de recuperação da palavra-passe.
 *
 * Inicializa a validação de apoio do formulário utilizado para solicitar
 * uma ligação de redefinição.
 *
 * O nome do campo `email` permanece inalterado por corresponder ao
 * contrato técnico da autenticação.
 *
 * @since 1.0.0
 * @version 2.2.0
 */

/**
 * Seletores utilizados na página de recuperação da palavra-passe.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.2.0
 * @version 1.0.0
 */
const SELETORES = Object.freeze({
    formulario:
        '#formulario-recuperar-palavra-passe',
});

/**
 * Inicia a validação do formulário de recuperação da palavra-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.2.0
 */
function iniciarValidacaoFormulario() {
    const formulario =
        document.querySelector(
            SELETORES.formulario,
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

/**
 * Inicia os comportamentos da página de recuperação da palavra-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.2.0
 */
function iniciarPaginaRecuperacaoPalavraPasse() {
    iniciarValidacaoFormulario();
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

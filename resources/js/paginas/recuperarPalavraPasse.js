import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Configura os comportamentos da página de recuperação da palavra-passe.
 *
 * @since 1.0.0
 * @version 3.0.0
 */

/**
 * Seletores utilizados na página.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.2.0
 * @version 2.0.0
 */
const SELETORES = Object.freeze({
    formulario:
        '#formulario-recuperar-palavra-passe',
});

/**
 * Obtém o comprimento máximo declarado no campo de e-mail.
 *
 * @param {HTMLFormElement} formulario Formulário pesquisado.
 *
 * @returns {number} Comprimento máximo positivo.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterComprimentoMaximoEmail(formulario) {
    const campo =
        formulario.elements.namedItem(
            'email',
        );

    if (
        campo instanceof HTMLInputElement
        && Number.isInteger(campo.maxLength)
        && campo.maxLength > 0
    ) {
        return campo.maxLength;
    }

    return 255;
}

/**
 * Inicia a validação do formulário de recuperação da palavra-passe.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function iniciarValidacaoFormulario() {
    const formulario =
        document.querySelector(
            SELETORES.formulario,
        );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    const comprimentoMaximoEmail =
        obterComprimentoMaximoEmail(
            formulario,
        );

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                email: [
                    'obrigatorio',
                    'email',
                    `maximo:${comprimentoMaximoEmail}`,
                ],
            },

            mensagens: {
                email: {
                    obrigatorio:
                        'Por favor, insere o teu endereço de e-mail.',

                    email:
                        'Por favor, insere um endereço de e-mail válido.',

                    maximo:
                        `O endereço de e-mail não pode ter mais de ${comprimentoMaximoEmail} caracteres.`,
                },
            },
        },
    );
}

/**
 * Inicia os comportamentos da página de recuperação da palavra-passe.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
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

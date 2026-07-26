import AlternadorPalavraPasse from '../modulos/AlternadorPalavraPasse';
import ValidadorFormulario from '../modulos/ValidadorFormulario';

/**
 * Script específico da página de redefinição da palavra-passe.
 *
 * Os nomes dos campos `password` e `password_confirmation` permanecem
 * inalterados por corresponderem ao contrato convencional de autenticação.
 *
 * @since 1.0.0
 * @version 2.1.0
 */

/**
 * Seletores utilizados na página.
 *
 * @type {Readonly<{
 *     formulario: string,
 *     alternadorPalavraPasse: string
 * }>}
 *
 * @since 2.1.0
 * @version 1.0.0
 */
const SELETORES = Object.freeze({
    formulario:
        'reset-password-form',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',
});

/**
 * Inicia a validação do formulário de redefinição da palavra-passe.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.1.0
 */
function iniciarValidacaoFormulario() {
    const formulario = document.getElementById(
        SELETORES.formulario,
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                password: [
                    'obrigatorio',
                    'minimo:8',
                ],

                password_confirmation: [
                    'obrigatorio',
                    'confirmado:password',
                ],
            },

            mensagens: {
                password: {
                    obrigatorio:
                        'Por favor, insere a palavra-passe.',

                    minimo:
                        'A palavra-passe deve ter, pelo menos, 8 caracteres.',
                },

                password_confirmation: {
                    obrigatorio:
                        'Por favor, confirma a palavra-passe.',

                    confirmado:
                        'As palavras-passe não coincidem.',
                },
            },
        },
    );
}

/**
 * Inicia os alternadores de visibilidade das palavras-passe.
 *
 * Cada alternador deve ser um botão com o atributo
 * `data-alvo-palavra-passe` a indicar o identificador do respetivo campo.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.1.0
 */
function iniciarAlternadoresPalavraPasse() {
    const alternadores = document.querySelectorAll(
        SELETORES.alternadorPalavraPasse,
    );

    if (alternadores.length === 0) {
        return;
    }

    new AlternadorPalavraPasse(
        alternadores,
    );
}

/**
 * Inicia os comportamentos da página de redefinição da palavra-passe.
 *
 * @returns {void}
 *
 * @since 1.0.0
 * @version 2.1.0
 */
function iniciarPaginaRedefinicaoPalavraPasse() {
    iniciarValidacaoFormulario();
    iniciarAlternadoresPalavraPasse();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaRedefinicaoPalavraPasse,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaRedefinicaoPalavraPasse();
}

import AlternadorPalavraPasse
    from '../modulos/AlternadorPalavraPasse';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Script específico da página de redefinição da palavra-passe.
 *
 * Inicializa a validação de apoio do formulário e os alternadores de
 * visibilidade dos campos de palavra-passe.
 *
 * Os nomes dos campos `password` e `password_confirmation` permanecem
 * inalterados por corresponderem ao contrato técnico da autenticação.
 *
 * @since 1.0.0
 * @version 2.2.0
 */

/**
 * Seletores utilizados na página de redefinição da palavra-passe.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.1.0
 * @version 1.1.0
 */
const SELETORES = Object.freeze({
    formulario:
        '#formulario-redefinir-palavra-passe',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',
});

/**
 * Inicia a validação do formulário de redefinição da palavra-passe.
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
                password: [
                    'obrigatorio',
                    'minimo:12',
                    'maiuscula',
                    'minuscula',
                    'numero',
                    'simbolo',
                ],

                password_confirmation: [
                    'obrigatorio',
                    'confirmado:password',
                ],
            },

            mensagens: {
                password: {
                    obrigatorio:
                        'Por favor, insere a nova palavra-passe.',

                    minimo:
                        'A nova palavra-passe deve ter, no mínimo, 12 caracteres.',

                    maiuscula:
                        'A nova palavra-passe deve incluir, pelo menos, uma letra maiúscula.',

                    minuscula:
                        'A nova palavra-passe deve incluir, pelo menos, uma letra minúscula.',

                    numero:
                        'A nova palavra-passe deve incluir, pelo menos, um número.',

                    simbolo:
                        'A nova palavra-passe deve incluir, pelo menos, um símbolo.',
                },

                password_confirmation: {
                    obrigatorio:
                        'Por favor, confirma a nova palavra-passe.',

                    confirmado:
                        'A confirmação não corresponde à nova palavra-passe.',
                },
            },
        },
    );
}

/**
 * Inicia os alternadores de visibilidade das palavras-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.2.0
 */
function iniciarAlternadoresPalavraPasse() {
    const alternadores =
        document.querySelectorAll(
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
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.2.0
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

import AlternadorPalavraPasse
    from '../modulos/AlternadorPalavraPasse';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Configura os comportamentos da página de início de sessão.
 *
 * @since 1.0.0
 */

/**
 * Seletores utilizados na página.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 */
const SELETORES = Object.freeze({
    formulario:
        '#formulario-iniciar-sessao',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',
});

/**
 * Obtém obrigatoriamente o comprimento máximo declarado num campo.
 *
 * @param {HTMLFormElement} formulario Formulário pesquisado.
 * @param {string} nomeCampo Nome HTML do campo.
 *
 * @returns {number} Comprimento máximo positivo.
 *
 * @throws {TypeError} Quando o campo ou o limite não são válidos.
 *
 * @since 2.0.0
 */
function obterComprimentoMaximo(
    formulario,
    nomeCampo,
) {
    const campo =
        formulario.elements.namedItem(
            nomeCampo,
        );

    if (
        !(campo instanceof HTMLInputElement)
        || !Number.isInteger(
            campo.maxLength,
        )
        || campo.maxLength <= 0
    ) {
        throw new TypeError(
            `O campo "${nomeCampo}" deve possuir um comprimento máximo válido.`,
        );
    }

    return campo.maxLength;
}

/**
 * Inicia a validação do formulário.
 *
 * @returns {void}
 *
 * @since 1.0.0
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
        obterComprimentoMaximo(
            formulario,
            'email',
        );

    const comprimentoMaximoPalavraPasse =
        obterComprimentoMaximo(
            formulario,
            'palavra_passe',
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

                palavra_passe: [
                    'obrigatorio',
                    `maximo:${comprimentoMaximoPalavraPasse}`,
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

                palavra_passe: {
                    obrigatorio:
                        'Por favor, insere a tua palavra-passe.',

                    maximo:
                        'A palavra-passe recebida é demasiado longa.',
                },
            },
        },
    );
}

/**
 * Inicia o alternador de visibilidade da palavra-passe.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarAlternadorPalavraPasse() {
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
 * Inicia os comportamentos da página de início de sessão.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarPaginaInicioSessao() {
    iniciarValidacaoFormulario();
    iniciarAlternadorPalavraPasse();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaInicioSessao,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaInicioSessao();
}

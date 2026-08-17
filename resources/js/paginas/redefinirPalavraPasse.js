import AlternadorPalavraPasse
    from '../modulos/AlternadorPalavraPasse';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Configura os comportamentos da página de redefinição da palavra-passe.
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
        '#formulario-redefinir-palavra-passe',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',
});

/**
 * Obtém obrigatoriamente um campo de palavra-passe através do respetivo nome.
 *
 * @param {HTMLFormElement} formulario Formulário pesquisado.
 * @param {string} nomeCampo Nome HTML do campo.
 *
 * @returns {HTMLInputElement} Campo encontrado.
 *
 * @throws {TypeError} Quando o campo esperado não existe.
 *
 * @since 2.0.0
 */
function obterCampoPalavraPasse(
    formulario,
    nomeCampo,
) {
    const campo =
        formulario.elements.namedItem(
            nomeCampo,
        );

    if (
        !(campo instanceof HTMLInputElement)
        || campo.type !== 'password'
    ) {
        throw new TypeError(
            `O formulário "${formulario.id}" deve possuir o campo de palavra-passe "${nomeCampo}".`,
        );
    }

    return campo;
}

/**
 * Obtém obrigatoriamente o comprimento mínimo declarado num campo.
 *
 * @param {HTMLInputElement} campo Campo pesquisado.
 *
 * @returns {number} Comprimento mínimo positivo.
 *
 * @throws {TypeError} Quando o limite não é válido.
 *
 * @since 2.0.0
 */
function obterComprimentoMinimo(
    campo,
) {
    if (
        !Number.isInteger(
            campo.minLength,
        )
        || campo.minLength <= 0
    ) {
        throw new TypeError(
            `O campo "${campo.name}" deve possuir um comprimento mínimo válido.`,
        );
    }

    return campo.minLength;
}

/**
 * Obtém obrigatoriamente o comprimento máximo declarado num campo.
 *
 * @param {HTMLInputElement} campo Campo pesquisado.
 *
 * @returns {number} Comprimento máximo positivo.
 *
 * @throws {TypeError} Quando o limite não é válido.
 *
 * @since 2.0.0
 */
function obterComprimentoMaximo(
    campo,
) {
    if (
        !Number.isInteger(
            campo.maxLength,
        )
        || campo.maxLength <= 0
    ) {
        throw new TypeError(
            `O campo "${campo.name}" deve possuir um comprimento máximo válido.`,
        );
    }

    return campo.maxLength;
}

/**
 * Inicia a validação do formulário de redefinição da palavra-passe.
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

    const campoPalavraPasse =
        obterCampoPalavraPasse(
            formulario,
            'palavra_passe',
        );

    const campoConfirmacao =
        obterCampoPalavraPasse(
            formulario,
            'confirmacao_palavra_passe',
        );

    const comprimentoMinimoPalavraPasse =
        obterComprimentoMinimo(
            campoPalavraPasse,
        );

    const comprimentoMaximoPalavraPasse =
        obterComprimentoMaximo(
            campoPalavraPasse,
        );

    const comprimentoMaximoConfirmacao =
        obterComprimentoMaximo(
            campoConfirmacao,
        );

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                palavra_passe: [
                    'obrigatorio',
                    `minimo:${comprimentoMinimoPalavraPasse}`,
                    `maximo:${comprimentoMaximoPalavraPasse}`,
                    'maiuscula',
                    'minuscula',
                    'numero',
                    'simbolo',
                ],

                confirmacao_palavra_passe: [
                    'obrigatorio',
                    `maximo:${comprimentoMaximoConfirmacao}`,
                    'confirmado:palavra_passe',
                ],
            },

            mensagens: {
                palavra_passe: {
                    obrigatorio:
                        'Por favor, insere a nova palavra-passe.',

                    minimo:
                        `A nova palavra-passe deve ter, pelo menos, ${comprimentoMinimoPalavraPasse} caracteres.`,

                    maximo:
                        'A nova palavra-passe é demasiado longa.',

                    maiuscula:
                        'A nova palavra-passe deve incluir, pelo menos, uma letra maiúscula.',

                    minuscula:
                        'A nova palavra-passe deve incluir, pelo menos, uma letra minúscula.',

                    numero:
                        'A nova palavra-passe deve incluir, pelo menos, um número.',

                    simbolo:
                        'A nova palavra-passe deve incluir, pelo menos, um símbolo.',
                },

                confirmacao_palavra_passe: {
                    obrigatorio:
                        'Por favor, confirma a nova palavra-passe.',

                    maximo:
                        'A confirmação da nova palavra-passe é demasiado longa.',

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
 * @returns {void}
 *
 * @since 1.0.0
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
 * @returns {void}
 *
 * @since 1.0.0
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

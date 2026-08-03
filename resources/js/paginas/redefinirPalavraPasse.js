import AlternadorPalavraPasse
    from '../modulos/AlternadorPalavraPasse';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Configura os comportamentos da página de redefinição da palavra-passe.
 *
 * @since 1.0.0
 * @version 3.0.0
 */

/**
 * Seletores utilizados na página.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.1.0
 * @version 2.0.0
 */
const SELETORES = Object.freeze({
    formulario:
        '#formulario-redefinir-palavra-passe',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',
});

/**
 * Obtém um campo textual através do respetivo nome.
 *
 * @param {HTMLFormElement} formulario Formulário pesquisado.
 * @param {string} nomeCampo Nome HTML do campo.
 *
 * @returns {HTMLInputElement|null} Campo encontrado ou nulo.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterCampo(
    formulario,
    nomeCampo,
) {
    const campo =
        formulario.elements.namedItem(
            nomeCampo,
        );

    return campo instanceof HTMLInputElement
        ? campo
        : null;
}

/**
 * Obtém o comprimento mínimo declarado num campo.
 *
 * @param {HTMLInputElement|null} campo Campo recebido.
 * @param {number} valorPredefinido Valor utilizado quando não existe limite.
 *
 * @returns {number} Comprimento mínimo positivo.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterComprimentoMinimo(
    campo,
    valorPredefinido,
) {
    if (
        campo instanceof HTMLInputElement
        && Number.isInteger(campo.minLength)
        && campo.minLength > 0
    ) {
        return campo.minLength;
    }

    return valorPredefinido;
}

/**
 * Obtém o comprimento máximo declarado num campo.
 *
 * @param {HTMLInputElement|null} campo Campo recebido.
 * @param {number} valorPredefinido Valor utilizado quando não existe limite.
 *
 * @returns {number} Comprimento máximo positivo.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterComprimentoMaximo(
    campo,
    valorPredefinido,
) {
    if (
        campo instanceof HTMLInputElement
        && Number.isInteger(campo.maxLength)
        && campo.maxLength > 0
    ) {
        return campo.maxLength;
    }

    return valorPredefinido;
}

/**
 * Inicia a validação do formulário de redefinição da palavra-passe.
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

    const campoPalavraPasse =
        obterCampo(
            formulario,
            'palavra_passe',
        );

    const campoConfirmacao =
        obterCampo(
            formulario,
            'confirmacao_palavra_passe',
        );

    const comprimentoMinimoPalavraPasse =
        obterComprimentoMinimo(
            campoPalavraPasse,
            12,
        );

    const comprimentoMaximoPalavraPasse =
        obterComprimentoMaximo(
            campoPalavraPasse,
            4096,
        );

    const comprimentoMaximoConfirmacao =
        obterComprimentoMaximo(
            campoConfirmacao,
            comprimentoMaximoPalavraPasse,
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
 * @version 2.0.0
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
 * @version 3.0.0
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

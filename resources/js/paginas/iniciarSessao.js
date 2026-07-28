import AlternadorPalavraPasse
    from '../modulos/AlternadorPalavraPasse';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Script específico da página de início de sessão.
 *
 * Inicializa a validação de apoio do formulário e o alternador de
 * visibilidade da palavra-passe.
 *
 * Os nomes dos campos `email`, `password` e `remember` permanecem
 * inalterados por corresponderem ao contrato técnico da autenticação.
 *
 * @since 1.0.0
 * @version 2.1.0
 */

/**
 * Seletores utilizados na página de início de sessão.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 * @version 1.1.0
 */
const SELETORES = Object.freeze({
    formulario:
        '#formulario-iniciar-sessao',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',
});

/**
 * Inicia a validação do formulário.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.1.0
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

                password: [
                    'obrigatorio',
                    'maximo:4096',
                ],
            },

            mensagens: {
                email: {
                    obrigatorio:
                        'Por favor, insere o teu endereço de e-mail.',

                    email:
                        'Por favor, insere um endereço de e-mail válido.',

                    maximo:
                        'O endereço de e-mail deve ter, no máximo, 255 caracteres.',
                },

                password: {
                    obrigatorio:
                        'Por favor, insere a tua palavra-passe.',

                    maximo:
                        'A palavra-passe recebida é demasiado extensa.',
                },
            },
        },
    );
}

/**
 * Inicia o alternador de visibilidade da palavra-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.1.0
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
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.1.0
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

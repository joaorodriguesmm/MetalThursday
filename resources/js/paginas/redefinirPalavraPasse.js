import AlternadorPalavraPasse
    from '../modulos/AlternadorPalavraPasse';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Script específico da página de redefinição da palavra-passe.
 *
 * Os seletores e nomes dos campos permanecem temporariamente inalterados
 * até à revisão da respetiva vista Blade.
 *
 * @since 1.0.0
 * @version 2.0.0
 */

/**
 * Inicia a validação do formulário de redefinição da palavra-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarValidacaoFormulario() {
    new ValidadorFormulario(
        '#reset-password-form',
        {
            password: [
                'required',
                'min:8',
            ],

            password_confirmation: [
                'required',
                'confirmed:password',
            ],
        },
        {
            password: {
                required:
                    'Por favor, insere a palavra-passe.',

                min:
                    'A palavra-passe deve ter, no mínimo, 8 caracteres.',
            },

            password_confirmation: {
                required:
                    'Por favor, insere a confirmação da palavra-passe.',

                confirmed:
                    'As palavras-passe não coincidem.',
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
 * @version 2.0.0
 */
function iniciarAlternadoresPalavraPasse() {
    new AlternadorPalavraPasse(
        '.password-toggle-icon',
    );
}

/**
 * Inicia os comportamentos da página de redefinição da palavra-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
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

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Script específico da página de recuperação da palavra-passe.
 *
 * Os seletores e os nomes dos campos permanecem temporariamente
 * inalterados até à revisão da respetiva vista Blade.
 *
 * @since 1.0.0
 * @version 2.0.0
 */

/**
 * Inicia os comportamentos da página de recuperação da palavra-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarPaginaRecuperacaoPalavraPasse() {
    new ValidadorFormulario(
        '#forgot-password-form',
        {
            email: [
                'required',
                'email',
                'max:255',
            ],
        },
        {
            email: {
                required:
                    'Por favor, insere o teu e-mail.',

                email:
                    'Por favor, insere um e-mail válido.',

                max:
                    'O e-mail deve ter, no máximo, 255 caracteres.',
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

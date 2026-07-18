/**
 * Script específico para a página de recuperação de password.
 *
 * @since 1.0
 * @version 1.0
 */
import FormValidator from '../modules/FormValidator';

/**
 * Define os comportamentos a executar após o carregamento da página.
 *
 * @since 1.0
 * @version 1.0
 */
document.addEventListener('DOMContentLoaded', () => {
    /**
     * Inicia o validador de formulário.
     *
     * @since 1.0
     * @version 1.0
     */
    new FormValidator(
        '#forgot-password-form',
        {
            email: ['required', 'email', 'max:255'],
        },
        {
            email: {
                required: 'Por favor, insere o teu e-mail.',
                email   : 'Por favor, insere um e-mail válido.',
                max     : 'O e-mail deve ter no máximo 255 caracteres.'
            }
        }
    );
});

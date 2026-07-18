/**
 * Script específico para a página de login.
 *
 * @since 1.0
 * @version 1.0
 */
import FormValidator from '../modules/FormValidator';
import PasswordToggle from '../modules/PasswordToggle';

/**
 * Define os comportamentos a executar após o carregamento da página.
 *
 * @since 1.0
 * @version 1.0
 */
document.addEventListener('DOMContentLoaded', () => {
    /**
     * Inicia o validador de formulários.
     *
     * @since 1.0
     * @version 1.0
     */
    new FormValidator(
        '#login-form',
        {
            email: ['required', 'email', 'max:255'],
            password: ['required'],
        },
        {
            email: {
                required: 'Por favor, insere o teu e-mail.',
                email: 'Por favor, insere um e-mail válido.',
                max: 'O e-mail deve ter menos de 255 caracteres.'
            },
            password: {
                required: 'Por favor, insere a palavra-passe.',
            },
        }
    );

    /**
     * Inicia o toggle de visibilidade para a palavra-passe.
     *
     * @since 1.0
     * @version 1.0
     */
    new PasswordToggle('.password-toggle-icon');
});

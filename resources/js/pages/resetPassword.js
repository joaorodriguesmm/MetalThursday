/**
 * Script específico para a página de redefinição de password.
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
        '#reset-password-form',
        {
            password             : ['required', 'min:8'],
            password_confirmation: ['required', 'confirmed:password'],
        },
        {
            password: {
                required: 'Por favor, insere a palavra-passe.',
                min     : 'A palavra-passe deve ter no mínimo 8 caracteres.',
            },
            password_confirmation: {
                required : 'Por favor, insere a confirmação da palavra-passe.',
                confirmed: 'As palavras-passe não coincidem.'
            },
        }
    );

    /**
     * Inicia o toggle de password.
     *
     * @since 1.0
     * @version 1.0
     */
    new PasswordToggle('.password-toggle-icon');
});

/**
 * Script específico para a página de edição de perfil.
 *
 * @since 1.0
 * @version 2.0
 */

// Importa todos os módulos necessários.
import FileValidator from '../modules/FileValidator';
import FormValidator from '../modules/FormValidator';
import PasswordToggle from '../modules/PasswordToggle';
import PermissionsSelector from '../modules/PermissionsSelector';
import ProfilePhotoHandler from '../modules/ProfilePhotoHandler';
import TooltipInitializer from '../modules/TooltipInitializer';

/**
 * Define os comportamentos a executar após o carregamento da página.
 *
 * @since 1.0
 * @version 1.0
 */
document.addEventListener('DOMContentLoaded', () => {
    /**
     * Inicia o gestor de foto de perfil.
     *
     * @since 1.0
     * @version 1.0
     */
    const photoHandler = new ProfilePhotoHandler(
        '#profile-photo-input',
        '#profile-photo-preview',
        '#avatar-initials'
    );

    /**
     * Inicia o validador de ficheiros.
     *
     * @since 1.0
     * @version 1.0
     */
    if (photoHandler && photoHandler.fileInput) {
        new FileValidator(photoHandler.fileInput, {
            allowedTypes          : ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            maxSize               : 10 * 1024 * 1024,
            errorMessageSelector  : '#photo-js-error',
            customFileTextSelector: '#custom-file-text',
            defaultFileText       : 'Escolher ficheiro',
            selectedFileText      : 'Alterar ficheiro',
            onInvalidFile         : () => {
                photoHandler.resetPreview();
            },
            onValidFile           : (file) => {
                photoHandler.previewImage(file);
            }
        });
    }

    /**
     * Inicia o validador para o formulário de atualização de perfil.
     *
     * @since 1.0
     * @version 1.0
     */
    new FormValidator(
        '#update-profile-form',
        {
            name: ['required', 'min:3', 'max:255'],
            email: ['required', 'email', 'max:255'],
        },
        {
            name: {
                required: 'Por favor, insere o teu nome.',
                min: 'O nome deve ter pelo menos 3 caracteres.',
                max: 'O nome deve ter menos de 255 caracteres.'
            },
            email: {
                required: 'Por favor, insere o teu e-mail.',
                email: 'Por favor, insere um e-mail válido.',
                max: 'O e-mail deve ter menos de 255 caracteres.'
            },
        }
    );

    /**
     * Inicia o selector de permissões.
     *
     * @since 1.0
     * @version 1.0
     */
    new PermissionsSelector('#perm-all', '.other-permission-item');

    /**
     * Inicia o validador para o formulário de atualização de password.
     *
     * @since 1.0
     * @version 1.0
     */
    new FormValidator(
        '#update-password-form',
        {
            current_password: ['required'],
            password: ['required', 'min:8'],
            password_confirmation: ['required', 'confirmed:password'],
        },
        {
            current_password: {
                required: 'Por favor, insere a tua palavra-passe atual.',
            },
            password: {
                required: 'Por favor, insere a nova palavra-passe.',
                min: 'A nova palavra-passe deve ter pelo menos 8 caracteres.',
            },
            password_confirmation: {
                required: 'Por favor, insere a confirmação da nova palavra-passe.',
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

    /**
     * Inicia o gestor de tooltips.
     *
     * @since 1.0
     * @version 1.0
     */
    new TooltipInitializer();
});

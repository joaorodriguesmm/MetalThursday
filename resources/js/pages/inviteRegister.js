
/**
 * Script específico para a página de registo por convite.
 *
 * @since 1.0
 * @version 1.0
 */
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
     * Inicia o gestor de fotos de perfil.
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
     * Inicia o validador para o formulário de registo por convite.
     *
     * @since 1.0
     * @version 1.0
     */
    new FormValidator(
        '#invite-register-form',
        {
            invite_code          : ['required'],
            name                 : ['required', 'min:3', 'max:255'],
            email                : ['required', 'email', 'max:255'],
            password             : ['required', 'min:8'],
            password_confirmation: ['required', 'confirmed:password'],
        },
        {
            invite_code: {
                required: 'Ocorreu um erro ao validar a integridade do convite. Recarrega a página e tenta novamente.',
            },
            name: {
                required: 'Por favor, insere o teu nome.',
                min     : 'O nome deve ter no mínimo 3 caracteres.',
                max     : 'O nome deve ter no máximo 255 caracteres.'
            },
            email: {
                required: 'Por favor, insere o teu e-mail.',
                email   : 'Por favor, insere um e-mail válido.',
                max     : 'O e-mail deve ter no máximo 255 caracteres.'
            },
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
     * Inicia o alternador de visibilidade da palavra-passe.
     *
     * @since 1.0
     * @version 1.0
     */
    new PasswordToggle('.password-toggle-icon');

    /**
     * Inicia o gestor de fotos de perfil.
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
            onInvalidFile         : (file) => {
                photoHandler.resetPreview();
            },
            onValidFile           : (file) => {
                photoHandler.previewImage(file);
            }
        });
    }

    /**
     * Inicia o gestor de permissões.
     *
     * @since 1.0
     * @version 1.0
     */
    new PermissionsSelector('#perm-all', '.other-permission-item');

    /**
     * Inicia os tooltips.
     *
     * @since 1.0
     * @version 1.0
     */
    new TooltipInitializer()
});

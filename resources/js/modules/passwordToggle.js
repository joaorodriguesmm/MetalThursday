/**
 * Gere a funcionalidade de alternar a visibilidade da password.
 *
 * @since 1.0
 * @version 1.0
 */
class PasswordToggle {
    /**
     * Cria um novo PasswordToggle.
     *
     * @param string|null toggleSelector - Seletor CSS para os botões/ícones que alternam a visibilidade (ex: '.password-toggle-icon').
     *
     * @since 1.0
     * @version 2.0
     */
    constructor(toggleSelector = '.password-toggle-icon') {
        this.toggleSelector = toggleSelector;
        this.init();
    }

    /**
     * Inicia o PasswordToggle.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        document.querySelectorAll(this.toggleSelector).forEach(button => {
            const targetId      = button.dataset.target;
            const passwordField = document.querySelector(`#${targetId}`);

            if (passwordField) {
                button.addEventListener('click', () => this.toggleVisibility(passwordField, button));
            }
        });
    }

    /**
     * Alterna a visibilidade do campo de password e o ícone.
     *
     * @param HTMLInputElement passwordField - O campo de input da password.
     * @param HTMLElement toggleButton - O botão/ícone que acionou a alternância.
     *
     * @since 1.0
     * @version 1.0
     */
    toggleVisibility(passwordField, toggleButton) {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);

        const icon = toggleButton.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash-fill');
        }
    }
}

export default PasswordToggle;

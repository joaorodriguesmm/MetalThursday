/**
 * Gere a alternância entre diferentes vistas numa página (ex: completa vs. simplificada).
 *
 * @since 1.0
 * @version 1.0
 */
class ViewToggler {
    /**
     * Cria um novo ViewToggler.
     *
     * @param object options - Opções de configuração.
     * @param string options.buttonSelector - Seletor do botão que aciona a alternância.
     * @param string options.inputSelector - Seletor do input escondido que guarda o tipo de vista.
     * @param string options.formSelector - Seletor do formulário a ser submetido na alteração.
     * @param object options.translations - Objeto com os valores traduzidos (ex: { full: 'completa', simplified: 'simplificada' }).
     *
     * @since 1.0
     * @version 1.0
     */
    constructor({ buttonSelector, inputSelector, formSelector, translations }) {
        this.toggleBtn    = document.querySelector(buttonSelector);
        this.viewInput    = document.querySelector(inputSelector);
        this.form         = document.querySelector(formSelector);
        this.translations = translations;

        if (!this.toggleBtn || !this.viewInput || !this.form || !this.translations) return;

        this.init();
    }

    /**
     * Inicia o ViewToggler.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
       this.toggleBtn.addEventListener('click', () => this.toggle());
    }

    /**
     * Altera a vista e atualiza o texto do botão.
     *
     * @since 1.0
     * @version 1.0
     */
    toggle() {
        const currentView = this.viewInput.value;
        const newView = currentView === this.translations.full
            ? this.translations.simplified
            : this.translations.full;

        this.viewInput.value = newView;
        localStorage.setItem('metalThursdayView', newView);
        this.form.submit();
    }

    /**
     * Atualiza o texto do botão de acordo com a vista atual.
     *
     * @since 1.0
     * @version 1.0
     */
    updateButtonText(viewValue) {
        if (viewValue === this.translations.simplified) {
            this.toggleBtn.textContent = 'Ver Vista Completa';
        } else {
            this.toggleBtn.textContent = 'Ver Vista Simplificada';
        }
    }
}

export default ViewToggler;

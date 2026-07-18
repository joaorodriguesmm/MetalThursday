/**
 * Gere a limpeza de formulários dentro de modais do Bootstrap.
 *
 * @since 1.0
 * @version 1.0
 */
class ModalFormCleaner {
    /**
     * Cria um novo ModalFormCleaner.
     *
     * @param string|null modalSelector - Seletor CSS para as modais.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(modalSelector = '.modal') {
        this.modals = document.querySelectorAll(modalSelector);
        if (this.modals.length === 0) return;
        this.init();
    }

    /**
     * Inicia o ModalFormCleaner.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        this.modals.forEach(modal => {
            modal.addEventListener('hidden.bs.modal', this.cleanForm.bind(this));
        });
    }

    /**
     * Limpa o formulário dentro da modal.
     *
     * @param Event event - Evento de fechamento da modal.
     *
     * @since 1.0
     * @version 1.0
     */
    cleanForm(event) {
        const form = event.target.querySelector('form');
        if (!form) return;

        form.reset();

        form.querySelectorAll('.tomselected').forEach(el => {
            if (el.tomselect) {
                el.tomselect.clear();
            }
        });

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
    }
}

export default ModalFormCleaner;

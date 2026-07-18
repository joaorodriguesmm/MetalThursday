/**
 * Gere as secções.
 *
 * @since 1.0
 * @version 1.0
 */
class SectionManager {
    /**
     * Cria um novo SectionManager.
     *
     * @param string containerSelector - Seletor CSS para o container principal.
     * @param string addButtonSelector - Seletor CSS para o botão de adicionar seção.
     * @param string templateSelector - Seletor CSS para o template da seção.
     * @param function onSectionAddedCallback - Função a ser executada quando uma seção é adicionada.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(containerSelector, addButtonSelector, templateSelector, onSectionAddedCallback) {
        this.container      = document.querySelector(containerSelector);
        this.addButton      = document.querySelector(addButtonSelector);
        this.template       = document.querySelector(templateSelector);
        this.onSectionAdded = onSectionAddedCallback;

        if (!this.container || !this.addButton || !this.template) return;

        this.index = this.container.querySelectorAll('.section-item').length;

        this.init();
    }

    /**
     * Inicia o SectionManager.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        this.addButton.addEventListener('click', () => this.add());
        this.container.addEventListener('click', (e) => this.remove(e));
        this.container.addEventListener('change', (e) => this.updateSectionUI(e));
    }

    /**
     * Adiciona uma secção.
     *
     * @since 1.0
     * @version 1.0
     */
    add() {
        const templateContent = this.template.innerHTML.replace(/__INDEX__/g, this.index);
        this.container.insertAdjacentHTML('beforeend', templateContent);

        const newSectionElement = this.container.lastElementChild;
        if (this.onSectionAdded) {
            this.onSectionAdded(newSectionElement);
        }
        this.index++;
    }

    /**
     * Remove uma secção.
     *
     * @param Event event - O evento 'click'.
     *
     * @since 1.0
     * @version 1.0
     */
    remove(event) {
        if (!event.target.classList.contains('remove-section-btn')) return;
        const sectionItem = event.target.closest('.section-item');
        sectionItem.querySelectorAll('.tomselected').forEach(select => {
            if (select.tomselect) select.tomselect.destroy();
        });
        sectionItem.remove();
    }

    /**
     * Atualiza a interface de uma secção.
     *
     * @param Event event - O evento 'change'.
     *
     * @since 1.0
     * @version 1.0
     */
    updateSectionUI(event) {
        if (!event.target.classList.contains('section-type-select')) return;
        const select                 = event.target;
        const hasDetails             = select.options[select.selectedIndex]?.dataset.hasDetails === 'true';
        const sectionItem            = select.closest('.section-item');
        const detailsContainers      = sectionItem.querySelectorAll('.section-details-row-1, .section-details-row-2');
        const titleRequiredIndicator = sectionItem.querySelector('.title-required-indicator');

        detailsContainers.forEach(container => {
            container.style.display = hasDetails ? '' : 'none';
        });

        const detailInputs = sectionItem.querySelectorAll('.section-details-row-1 input, .section-details-row-1 select, .section-details-row-2 input');
        detailInputs.forEach(input => {
            input.required = hasDetails;
        });

        if (titleRequiredIndicator) {
            titleRequiredIndicator.style.display = hasDetails ? '' : 'none';
        }
    }
}
export default SectionManager;

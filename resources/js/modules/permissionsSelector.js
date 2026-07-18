/**
 * Gere o seletor de permissões.
 *
 * @since 1.0
 * @version 1.0
 */
class PermissionsSelector {
    /**
     * Cria um novo PermissionsSelector.
     *
     * @param string allPermissionsCheckboxSelector - Seletor CSS para a checkbox "Todas as Permissões" (ex: '#perm-all').
     * @param string otherPermissionItemsSelector - Seletor CSS para os elementos que contêm as outras permissões (ex: '.other-permission-item').
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(allPermissionsCheckboxSelector, otherPermissionItemsSelector) {
        this.allPermissionsCheckbox = document.querySelector(allPermissionsCheckboxSelector);
        this.otherPermissionItems   = document.querySelectorAll(otherPermissionItemsSelector);

        if (!this.allPermissionsCheckbox) {
            return;
        }

        this.init();
    }

    /**
     * Inicia o PermissionsSelector.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        this.allPermissionsCheckbox.addEventListener('change', this.updatePermissionsState.bind(this));
        this.updatePermissionsState();
    }

    /**
     * Atualiza a visibilidade das outras permissões com base no estado da checkbox "Todas as Permissões".
     * Se "Todas as Permissões" estiver selecionada, as outras são escondidas e desmarcadas.
     * Caso contrário, as outras permissões são mostradas.
     *
     * @since 1.0
     * @version 1.0
     */
    updatePermissionsState() {
        if (this.allPermissionsCheckbox.checked) {
            this.otherPermissionItems.forEach(item => {
                item.style.display = 'none';
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
            });
        } else {
            this.otherPermissionItems.forEach(item => {
                item.style.display = 'block';
            });
        }
    }
}

export default PermissionsSelector;

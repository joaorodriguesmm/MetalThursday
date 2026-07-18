import TomSelect from 'tom-select';

/**
 * Gere a criação e remoção dinâmica de filtros de pesquisa numa página.
 *
 * @since 1.0
 * @version 1.0
 */
class DynamicFilterManager {
    /**
     * Cria um novo DynamicFilterManager.
     *
     * @param object options - Opções de configuração.
     * @param string options.dropdownSelector - Seletor do <select> para adicionar novos filtros.
     * @param string options.containerSelector - Seletor do elemento que irá conter os filtros ativos.
     * @param object options.filterData - Objeto JS com os dados para os selects (autores, bandas, etc.).
     * @param object options.availableFilters - Objeto JS que define os filtros disponíveis.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor({ dropdownSelector, containerSelector, filterData, availableFilters }) {
        this.addFilterDropdown = document.querySelector(dropdownSelector);
        this.activeFiltersArea = document.querySelector(containerSelector);
        this.filterData = filterData;
        this.availableFilters = availableFilters;

        if (!this.addFilterDropdown || !this.activeFiltersArea) return;

        this.activeTomSelects = {};
        this.init();
    }

    /**
     * Inicia o DynamicFilterManager.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        this.initFromUrl();
        this.addFilterDropdown.addEventListener('change', (e) => this.handleFilterAdd(e));
        this.activeFiltersArea.addEventListener('click', (e) => this.handleFilterRemove(e));
    }

    /**
     * Renderiza um novo filtro de pesquisa.
     *
     * @param Event event - O evento 'change'.
     *
     * @since 1.0
     * @version 1.0
     */
    handleFilterAdd(event) {
        const selectedOption = event.target.options[event.target.selectedIndex];
        if (selectedOption.value) {
            this.render(selectedOption.value);
            event.target.value = '';
        }
    }

    /**
     * Remove um filtro de pesquisa.
     *
     * @param Event event - O evento 'click'.
     *
     * @since 1.0
     * @version 1.0
     */
    handleFilterRemove(event) {
        const removeBtn = event.target.closest('[data-remove-filter]');
        if (removeBtn) {
            const filterToRemove = removeBtn.getAttribute('data-remove-filter');
            if (this.activeTomSelects[filterToRemove]) {
                this.activeTomSelects[filterToRemove].destroy();
                delete this.activeTomSelects[filterToRemove];
            }
            removeBtn.closest('[data-filter-name]').remove();
        }
    }

    /**
     * Inicia os filtros de pesquisa pela URL.
     *
     * @since 1.0
     * @version 1.0
     */
    initFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);

        for (const [key, value] of urlParams.entries()) {
            if (key.startsWith('filtro_')) {
                const paramFromUrl = key.substring(7);
                const foundFilter = Object.values(this.availableFilters).find(f => f.param === paramFromUrl);

                if (foundFilter) {
                    this.render(foundFilter.key, value);
                }
            }
        }
    }


    /**
     * Renderiza um novo filtro de pesquisa.
     *
     * @param string filterKey - Chave do filtro.
     * @param string currentValue - Valor atual do filtro.
     *
     * @since 1.0
     * @version 1.0
     */
    render(filterKey, currentValue = '') {
        const filterConfig = this.availableFilters[filterKey];
        if (!filterConfig) return;

        const filterName = `filtro_${filterConfig.param}`;
        if (document.querySelector(`[data-filter-name="${filterName}"]`)) return;

        let inputField;
        switch (filterConfig.type) {
            case 'select':
                const options = this.filterData[filterKey + 's'] || [];
                const placeholder = `<option value="" ${currentValue ? '' : 'selected'}>Seleciona uma opção</option>`;
                const optionsHtml = options.map(opt => `<option value="${opt.id}" ${currentValue == opt.id ? 'selected' : ''}>${opt.name}</option>`).join('');
                inputField = `<select name="${filterName}" class="form-select bg-secondary text-white border-secondary">${placeholder}${optionsHtml}</select>`;
                break;
            case 'date':
                inputField = `<input type="date" name="${filterName}" class="form-control bg-dark text-white border-secondary" value="${currentValue}">`;
                break;
            case 'yes_no':
                const selectedValue = currentValue || 'yes';
                inputField = `<select name="${filterName}" class="form-select bg-secondary text-white border-secondary"><option value="yes" ${selectedValue === 'yes' ? 'selected' : ''}>Sim</option><option value="no" ${selectedValue === 'no' ? 'selected' : ''}>Não</option></select>`;
                break;
            default: return;
        }

        const componentHtml = `<div class="col-md-4 mb-3" data-filter-name="${filterName}"><div class="card bg-secondary h-100"><div class="card-body p-2"><div class="d-flex justify-content-between align-items-center mb-2"><label class="small text-white">${filterConfig.label}</label><button type="button" class="btn-close btn-close-white" data-remove-filter="${filterName}"></button></div>${inputField}</div></div></div>`;
        this.activeFiltersArea.insertAdjacentHTML('beforeend', componentHtml);

        if (filterConfig.type === 'select') {
            const newSelect = this.activeFiltersArea.querySelector(`[name="${filterName}"]`);
            this.activeTomSelects[filterName] = new TomSelect(newSelect, {
                // Configuração do TomSelect, se necessário
            });
        }
    }
}

export default DynamicFilterManager;

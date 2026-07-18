/**
 * Gere as instâncias de TomSelect.
 *
 * @since 1.0
 * @version 1.0
 */
class TomSelectInitializer {
    /**
     * Cria um novo TomSelectInitializer.
     *
     * @param string|null selector - Seletor CSS para os elementos <select> a serem inicializados.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(selector = 'select.tom-select-single, select.tom-select-multiple') {
        this.selector  = selector;
        this.instances = {};

        this.initAll();
    }

    /**
     * Inicia os TomSelects na página que correspondem ao seletor.
     *
     * @since 1.0
     * @version 1.0
     */
    initAll() {
        document.querySelectorAll(this.selector).forEach(el => this.init(el));
    }

    /**
     * Inicializa uma única instância de TomSelect num elemento.
     *
     * @param HTMLElement el - O elemento <select> a ser inicializado.
     * @returns TomSelect|null A instância criada ou null se já estiver inicializada.
     *
     * @since 1.0
     * @version 1.0
     */
    init(el) {
        if (el.tomselect) {
            return null;
        }

        const config = {
            render: {
                no_results: (data, escape) => `<div class="no-results">Nenhum resultado para "${escape(data.input)}".</div>`,
            },
            plugins: el.multiple ? ['remove_button', 'clear_button'] : ['clear_button']
        };

        const instance = new TomSelect(el, config);
        if (el.id) {
            this.instances[el.id] = instance;
        }
        return instance;
    }

    /**
     * Obtém uma instância de TomSelect guardada pelo seu ID.
     *
     * @param string id - O Id do elemento <select>.
     * @returns TomSelect|undefined A instância de TomSelect.
     *
     * @since 1.0
     * @version 1.0
     */
    getInstance(id) {
        return this.instances[id];
    }
}

export default TomSelectInitializer;

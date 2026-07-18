import { Tooltip } from 'bootstrap';

/**
 * Gere os tooltips.
 *
 * @since 1.0
 * @version 1.0
 */
class TooltipInitializer {
    /**
     * Cria um novo TooltipInitializer.
     *
     * @param string|null tooltipSelector - Seletor CSS para os elementos que contém tooltips.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(tooltipSelector = '[data-bs-toggle="tooltip"]') {
        this.tooltipSelector = tooltipSelector;
        this.init();
    }

    /**
     * Inicia o TooltipInitializer.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        const tooltipTriggerList = document.querySelectorAll(this.tooltipSelector);
        if (tooltipTriggerList.length === 0) {
            return;
        }

        tooltipTriggerList.forEach(tooltipTriggerEl => {
            if (typeof Tooltip !== 'undefined') {
                new Tooltip(tooltipTriggerEl);
            }
        });
    }
}

export default TooltipInitializer;

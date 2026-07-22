import AjaxFormHandler from './AjaxFormHandler';
import ValidadorFormulario from '../modulos/ValidadorFormulario';

/**
 * Gera as modais.
 *
 * @since 1.0
 * @version 1.0
 */
class ModalManager {
    /**
     * Cria um novo ModalManager.
     *
     * @param Array modalConfigs - Um array de objetos de configuração, um para cada modal.
     * @param object tomSelects - A instância do TomSelectInitializer para adicionar novas opções.
     */
    constructor(modalConfigs, tomSelects) {
        this.modalConfigs = modalConfigs;
        this.tomSelects   = tomSelects;
        this.init();
    }

    /**
     * Inicia o ModalManager.
     *
     * @since 1.0
     * @version 1.0
     */
    init() {
        this.modalConfigs.forEach(config => {
            const form = document.getElementById(config.formId);
            if (!form) return;

            const ajaxHandler = new AjaxFormHandler(
                config.formId,
                config.url,
                (responseData) => {
                    if (config.onSuccess) {
                        config.onSuccess(responseData, this.tomSelects);
                    }
                }
            );

            const validator = new FormValidator(
                `#${config.formId}`,
                config.validationRules || {},
                config.validationMessages || {}
            );

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (validator.validateAllStatic()) {
                    ajaxHandler.submit();
                }
            });
        });
    }
}

export default ModalManager;

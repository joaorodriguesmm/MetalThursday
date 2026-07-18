/**
 * Gera a validação de formulários.
 *
 * @since 1.0
 * @version 1.0
 */
class FormValidator {
    /**
     * Cria um novo FormValidator.
     *
     * @param string formSelector - O seletor CSS do formulário a ser validado.
     * @param Object|null rules - Um objeto onde as chaves são os nomes dos campos (name attribute) e os valores são arrays de funções de validação. Ex: { email: [ 'required', 'email' ], password: [ 'required', 'min:8' ] }.
     * @param Object|null messages - Mensagens de erro personalizadas para regras específicas. Ex: { email: { required: 'O e-mail é obrigatório.' } }.
     * @param function|null onSuccessCallback - Função a ser executada quando o formulário for submetido sem erros de validação.
     * @param function|null customValidator - Função de validação personalizada.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(formSelector, rules = {}, messages = {}, onSuccessCallback = null, customValidator = null) {
        this.form = document.querySelector(formSelector);
        if (!this.form) return;

        this.rules              = rules;
        this.messages           = messages;
        this.onSuccess          = onSuccessCallback;
        this.customValidator    = customValidator;
        this.errors             = {};
        this.hasAttemptedSubmit = false;

        this.form.setAttribute('novalidate', '');
        this.setupEventListeners();
    }

    /**
     * Configura os event listeners para o formulário e os campos.
     *
     * @since 1.0
     * @version 1.0
     */
    setupEventListeners() {
        this.form.addEventListener('submit', (e) => {
            this.hasAttemptedSubmit = true;

            const isStaticValid = this.validateAllStatic();
            const isCustomValid = this.customValidator ? this.customValidator(this) : true;

            if (!isStaticValid || !isCustomValid) {
                e.preventDefault();
                return;
            }

            if (this.onSuccess) {
                e.preventDefault();
                this.onSuccess(this.form);
            }
        });

        const realTimeValidationEvents = ['input', 'change', 'focusout'];
        realTimeValidationEvents.forEach(eventType => {
            this.form.addEventListener(eventType, (e) => {
                if (this.hasAttemptedSubmit && e.target.name) {
                    this.validateField(e.target);
                }
            });
        });
    }

    /**
     * Valida apenas os campos estáticos definidos nas regras.
     *
     * @returns boolean - True se todos os campos são validados, false caso contrário.
     *
     * @since 1.0
     * @version 1.0
     */
    validateAllStatic() {
        let isValid = true;
        for (const fieldName in this.rules) {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field && !this.validateField(field)) {
                isValid = false;
            }
        }
        return isValid;
    }

    /**
     * Valida um campo específico.
     *
     * @param HTMLElement field - O elemento do campo a ser validado.
     * @returns boolean - True se o campo é válido, false caso contrário.
     *
     * @since 1.0
     * @version 1.0
     */
    validateField(field) {
        const fieldName  = field.name;
        const fieldRules = this.rules[fieldName] || [];
        const fieldValue = field.value;
        let fieldErrors  = [];

        for (const rule of fieldRules) {
            const errorMessage = this.getValidationErrorMessage(field, rule, fieldValue);
            if (errorMessage) {
                fieldErrors.push(errorMessage);
                break;
            }
        }

        this.errors[fieldName] = fieldErrors;
        this.displayFieldErrors(field, fieldErrors);
        return fieldErrors.length === 0;
    }

    /**
     * Valida um campo com regras e mensagens personalizadas.
     *
     * @param HTMLElement field - O elemento do campo a ser validado.
     * @param array rules - Um array de regras de validação.
     * @param array messages - Um array de mensagens de erro personalizadas.
     * @returns boolean - True se o campo é válido, false caso contrário.
     *
     * @since 1.0
     * @version 1.0
     */
    validateFieldWithRules(field, rules, messages) {
        const originalRules    = this.rules[field.name];
        const originalMessages = this.messages[field.name];

        this.rules[field.name]    = rules;
        this.messages[field.name] = messages;

        const isValid = this.validateField(field);

        this.rules[field.name]    = originalRules;
        this.messages[field.name] = originalMessages;

        return isValid;
    }

    /**
     * Obtém a mensagem de erro para uma regra específica.
     *
     * @param HTMLElement field - O elemento do campo.
     * @param string rule - A string da regra (e.g., 'required', 'min:8').
     * @param mixed value - O valor do campo.
     * @returns string|null - A mensagem de erro ou null se o campo é válido para a regra.
     *
     * @since 1.0
     * @version 1.0
     */
    getValidationErrorMessage(field, rule, value) {
        const [ruleName, ruleParam] = rule.split(':');
        let isValid = true;

        if (!this.rules[field.name]?.includes('required') && value.trim() === '') {
            return null;
        }

        switch (ruleName) {
            case 'required': isValid = value.trim() !== ''; break;
            case 'email': isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value); break;
            case 'min': isValid = value.length >= parseInt(ruleParam, 10); break;
            case 'max': isValid = value.length <= parseInt(ruleParam, 10); break;
            case 'confirmed':
                const originalField = this.form.querySelector(`[name="${ruleParam}"]`);
                isValid             = originalField ? value === originalField.value : false;
                break;
            case 'after_or_equal':
                const otherField = this.form.querySelector(`[name="${ruleParam}"]`);
                if (otherField && value && otherField.value) {
                    isValid = new Date(value) >= new Date(otherField.value);
                } else {
                    isValid = true;
                }
                break;
            case 'date':
                isValid = value.trim() === '' || !isNaN(new Date(value).getTime());
                break;
            default: isValid = true; break;
        }

        if (!isValid) {
            const customMessage = this.messages[field.name]?.[ruleName];
            if (customMessage) return customMessage;

            const friendlyName = this.getFriendlyFieldName(field);
            switch (ruleName) {
                case 'required': return `O campo '${friendlyName}' é obrigatório.`;
                case 'email': return 'Por favor, insira um e-mail válido.';
                case 'min': return `O campo '${friendlyName}' deve ter pelo menos ${ruleParam} caracteres.`;
                case 'max': return `O campo '${friendlyName}' não pode ter mais de ${ruleParam} caracteres.`;
                case 'confirmed': return `A confirmação não coincide.`;
                case 'after_or_equal': return `O campo '${friendlyName}' deve ser uma data igual ou posterior a '${otherFriendlyName}'.`;
                case 'date': return `O campo '${friendlyName}' tem de conter uma data válida.`;
                default: return `O campo '${friendlyName}' é inválido.`;
            }
        }
        return null;
    }

    /**
     * Obtém o nome amigável do campo.
     *
     * @param HTMLElement field - O elemento do campo.
     * @returns string - O nome amigável do campo.
     *
     * @since 1.0
     * @version 1.0
     */
    getFriendlyFieldName(field) {
        if (field.labels && field.labels[0]) {
            const labelNode = field.labels[0].cloneNode(true);
            labelNode.querySelector('span.text-danger')?.remove();
            return labelNode.textContent.trim();
        }
        return field.name;
    }

    /**
     * Exibe ou limpa as mensagens de erro para um campo.
     *
     * @param HTMLElement field - O elemento do campo.
     * @param Array errors - Um array de mensagens de erro para o campo.
     *
     * @since 1.0
     * @version 1.0
     */
    displayFieldErrors(field, errors) {
        const formGroup = field.closest('.form-field-group');
        if (!formGroup) return;
        const feedbackElement = formGroup.querySelector('.invalid-feedback');
        if (!feedbackElement) return;
        if (errors.length > 0) {
            feedbackElement.textContent = errors[0];
            field.classList.add('is-invalid');
            feedbackElement.style.display = 'block';
        } else {
            feedbackElement.textContent = '';
            field.classList.remove('is-invalid');
            feedbackElement.style.display = 'none';
        }
    }
}

export default FormValidator;

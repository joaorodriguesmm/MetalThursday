/**
 * Gere a validação de ficheiros.
 *
 * @since 1.0
 * @version 1.0
 */
class FileValidator {
    /**
     * Cria um novo FileValidator.
     *
     * @param HTMLElement fileInput - O elemento input de tipo 'file'.
     * @param Object options - Opções de validação e mensagens.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(fileInput, options = {}) {
        if (!fileInput || fileInput.type !== 'file') {
            return;
        }

        this.fileInput = fileInput;
        this.options = {
            allowedTypes          : [],
            maxSize               : 0,
            errorMessageSelector  : null,
            customFileTextSelector: null,
            onInvalidFile         : null,
            onValidFile           : null,
            defaultFileText       : 'Escolher ficheiro',
            selectedFileText      : 'Alterar ficheiro',
            ...options
        };

        this.errorMessageElement   = this.options.errorMessageSelector ? document.querySelector(this.options.errorMessageSelector) : null;
        this.customFileTextElement = this.options.customFileTextSelector ? document.querySelector(this.options.customFileTextSelector) : null;

        this.setupEventListeners();
        this.updateCustomFileText(null);
    }

    /**
     * Configura os event listeners para o input de ficheiro.
     *
     * @since 1.0
     * @version 1.0
     */
    setupEventListeners() {
        this.fileInput.addEventListener('change', this.handleChange.bind(this));
    }

    /**
     * Lida com a mudança no input de ficheiro.
     *
     * @param Event event - O evento de mudança.
     *
     * @since 1.0
     * @version 1.0
     */
    handleChange(event) {
        this.clearError();
        const file = event.target.files[0];

        if (!file) {
            this.updateCustomFileText(null);
            this.clearError();
            if (this.options.onInvalidFile) {
                this.options.onInvalidFile(null);
            }
            return;
        }

        let isValid      = true;
        let errorMessage = '';

        if (this.options.allowedTypes.length > 0 && !this.options.allowedTypes.includes(file.type)) {
            isValid      = false;
            errorMessage = `O tipo de ficheiro "${file.type}" não é permitido. Tipos permitidos: ${this.options.allowedTypes.map(t => t.split('/')[1].toUpperCase()).join(', ')}.`;
        }

        if (isValid && this.options.maxSize > 0 && file.size > this.options.maxSize) {
            isValid      = false;
            errorMessage = `O ficheiro é muito grande. O tamanho máximo permitido é ${this.formatBytes(this.options.maxSize)}.`;
        }

        if (!isValid) {
            this.displayError(errorMessage);
            this.fileInput.value = '';
            this.updateCustomFileText(null);
            if (this.options.onInvalidFile) {
                this.options.onInvalidFile(file);
            }
        } else {
            this.updateCustomFileText(this.options.selectedFileText);
            if (this.options.onValidFile) {
                this.options.onValidFile(file);
            }
        }
    }

    /**
     * Exibe uma mensagem de erro.
     *
     * @param string message - A mensagem de erro a exibir.
     *
     * @since 1.0
     * @version 1.0
     */
    displayError(message) {
        this.fileInput.classList.add('is-invalid');
        if (this.errorMessageElement) {
            this.errorMessageElement.textContent   = message;
            this.errorMessageElement.style.display = 'block';
        }
    }

    /**
     * Limpa qualquer mensagem de erro.
     *
     * @since 1.0
     * @version 1.0
     */
    clearError() {
        this.fileInput.classList.remove('is-invalid');
        if (this.errorMessageElement) {
            this.errorMessageElement.textContent   = '';
            this.errorMessageElement.style.display = 'none';
        }
    }

    /**
     * Atualiza o texto exibido para o nome do ficheiro.
     *
     * @param string|null fileName - O nome do ficheiro a exibir, ou null para o texto padrão.
     *
     * @since 1.0
     * @version 1.0
     */
    updateCustomFileText(fileName = null) {
        if (this.customFileTextElement) {
            this.customFileTextElement.textContent = fileName || this.options.defaultFileText;
        }
    }

    /**
     * Formata o tamanho em bytes para uma string legível.
     *
     * @param number bytes - O número de bytes.
     * @param number decimals - Número de casas decimais.
     * @returns string - O tamanho formatado.
     *
     * @since 1.0
     * @version 1.0
     */
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k     = 1024;
        const dm    = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i     = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
}

export default FileValidator;

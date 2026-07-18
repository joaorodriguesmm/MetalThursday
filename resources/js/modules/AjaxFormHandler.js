import Swal from 'sweetalert2';
import axios from 'axios';
import { Modal } from 'bootstrap';

/**
 * Gere a submissão de formulários via AJAX.
 *
 * @since 1.0
 * @version 1.0
 */
class AjaxFormHandler {
    /**
     * Cria um novo AjaxFormHandler.
     *
     * @param string formId - O Id do formulário.
     * @param string url - A URL para enviar o formulário.
     * @param function onSuccessCallback - A função a ser chamada quando o formulário for submetido com sucesso.
     *
     * @since 1.0
     * @version 1.0
     */
    constructor(formId, url, onSuccessCallback) {
        this.form = document.getElementById(formId);
        if (!this.form) return;

        this.url = url;
        this.onSuccess = onSuccessCallback;
        this.submitButton = this.form.querySelector('button[type="submit"]');
        this.originalButtonText = this.submitButton ? this.submitButton.innerHTML : 'Guardar';
    }

    /**
     * Submete o formulário via AJAX.
     *
     * @since 1.0
     * @version 1.0
     */
    async submit() {
        if (this.submitButton && this.submitButton.disabled) return;

        this.setButtonLoading(true);
        this.clearErrors();

        try {
            const formData = new FormData(this.form);
            const response = await axios.post(this.url, formData);

            document.dispatchEvent(new CustomEvent('ajax-form:success', {
                detail: { formId: this.form.id, responseData: response.data }
            }));

            const successMessage = this.form.dataset.successMessage || 'Ação concluída com sucesso.';
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: successMessage,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });

            if (this.onSuccess) {
                this.onSuccess(response.data);
            }

            const modalEl = this.form.closest('.modal');
            if (modalEl) {
                const modalInstance = Modal.getOrCreateInstance(modalEl);
                modalInstance.hide();
            } else {
                this.form.reset();
            }
        } catch (error) {
            if (error.response && error.response.status === 422) {
                this.handleValidationErrors(error.response.data.errors);
            } else {
                Swal.fire('Erro', 'Ocorreu um erro inesperado.', 'error');
            }
        } finally {
            this.setButtonLoading(false);
        }
    }

    /**
     * Trata os erros de validação de formulários.
     *
     * @param object errors - Erros de validação.
     *
     * @since 1.0
     * @version 1.0
     */
    handleValidationErrors(errors) {
        for (const fieldKey in errors) {
            const baseFieldName = fieldKey.split('.')[0];
            const input = this.form.querySelector(`[name="${baseFieldName}"], [name="${baseFieldName}[]"]`);
            if (input) {
                const feedback = input.closest('.form-field-group, .flex-grow-1')?.querySelector('.invalid-feedback');
                input.classList.add('is-invalid');
                if (feedback) {
                    feedback.textContent = errors[fieldKey][0];
                    feedback.style.display = 'block';
                }
            }
        }
    }

    /**
     * Limpa os erros de formulários.
     *
     * @since 1.0
     * @version 1.0
     */
    clearErrors() {
        this.form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        this.form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
    }

    /**
     * Define o estado de carregamento de um botão.
     *
     * @param boolean isLoading - Estado de carregamento.
     *
     * @since 1.0
     * @version 1.0
     */
    setButtonLoading(isLoading) {
        if (!this.submitButton) return;
        if (isLoading) {
            this.submitButton.disabled = true;
            this.submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> A processar...`;
        } else {
            this.submitButton.disabled = false;
            this.submitButton.innerHTML = this.originalButtonText;
        }
    }
}

export default AjaxFormHandler;
